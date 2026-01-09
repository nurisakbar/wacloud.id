<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSession;
use App\Services\ApiUsageService;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SessionApiController extends Controller
{
    protected ApiUsageService $usageService;
    protected WahaService $wahaService;

    public function __construct(ApiUsageService $usageService, WahaService $wahaService)
    {
        $this->usageService = $usageService;
        $this->wahaService = $wahaService;
    }

    /**
     * List all sessions
     */
    public function index(Request $request)
    {
        $startTime = microtime(true);
        
        $sessions = WhatsAppSession::where('user_id', $request->user->id)
            ->where('status', 'connected')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->session_id,
                    'name' => $session->session_name,
                    'status' => $session->status,
                    'created_at' => $session->created_at->toIso8601String(),
                ];
            });

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Get session details
     */
    public function show(Request $request, $sessionId)
    {
        $startTime = microtime(true);
        
        $session = WhatsAppSession::where('session_id', $sessionId)
            ->where('user_id', $request->user->id)
            ->first();

        if (!$session) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $session->session_id,
                'name' => $session->session_name,
                'status' => $session->status,
                'created_at' => $session->created_at->toIso8601String(),
                'last_activity_at' => $session->last_activity_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get session status
     */
    public function status(Request $request, $sessionId)
    {
        $startTime = microtime(true);
        
        $session = WhatsAppSession::where('session_id', $sessionId)
            ->where('user_id', $request->user->id)
            ->first();

        if (!$session) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $session->status,
                'is_connected' => $session->isConnected(),
            ],
        ]);
    }

    /**
     * Create a new device/session
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);
        
        // Validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'regex:/^08[0-9]{8,11}$/',
            ],
        ], [
            'name.required' => 'Device name is required',
            'phone_number.required' => 'Phone number is required',
            'phone_number.regex' => 'Invalid phone number format. Use format: 081395777706 (10-13 digits, starting with 08)',
        ]);

        $user = $request->user;
        
        // Normalize phone number using PhoneNumberHelper (convert to +62 format for database)
        $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($request->phone_number);
        
        if (!$phoneNumber) {
            $this->usageService->log($request, 400, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Invalid phone number format. Use format: 081395777706',
            ], 400);
        }
        
        // Check subscription plan limit
        $sessionsLimit = $this->getUserSessionsLimit($user);
        $activeSessionsCount = WhatsAppSession::where('user_id', $user->id)
            ->whereIn('status', ['pairing', 'connected'])
            ->count();
        
        if ($activeSessionsCount >= $sessionsLimit) {
            $this->usageService->log($request, 403, $startTime);
            return response()->json([
                'success' => false,
                'error' => "You have reached your device limit ({$sessionsLimit}). Please delete an existing device first or upgrade your plan.",
            ], 403);
        }
        
        // Generate unique session ID
        $sessionId = Str::random(16);

        \Log::info('SessionApiController: Creating device', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber,
        ]);

        // Check if session already exists in WAHA
        $statusResult = $this->wahaService->getSessionStatus($sessionId);
        $sessionExistsInWaha = $statusResult['success'];
        $wahaStatus = $statusResult['status'] ?? null;
        
        // Only stop and restart if session exists and is in bad state
        if ($sessionExistsInWaha && !in_array($wahaStatus, ['SCAN_QR_CODE', 'WORKING', 'STARTING'])) {
            \Log::info('SessionApiController: Stopping existing session in bad state', [
                'current_status' => $wahaStatus,
            ]);
            $this->wahaService->stopSession($sessionId);
            usleep(500000); // 0.5 seconds
        }
        
        // Create session in WAHA if not exists or needs restart
        if (!$sessionExistsInWaha || !in_array($wahaStatus, ['SCAN_QR_CODE', 'WORKING', 'STARTING'])) {
            $wahaResult = $this->wahaService->createSession($sessionId);
            
            if (!$wahaResult['success']) {
                $errorMsg = $wahaResult['error'] ?? '';
                if (str_contains($errorMsg, 'already started') || str_contains($errorMsg, 'already exists')) {
                    \Log::info('SessionApiController: Session already exists, rechecking status');
                    $statusResult = $this->wahaService->getSessionStatus($sessionId);
                    $wahaStatus = $statusResult['status'] ?? 'unknown';
                } else {
                    \Log::error('SessionApiController: Failed to create session', [
                        'error' => $wahaResult['error'],
                    ]);
                    $this->usageService->log($request, 500, $startTime);
                    return response()->json([
                        'success' => false,
                        'error' => $wahaResult['error'] ?? 'Failed to create device',
                    ], 500);
                }
            } else {
                // Wait for session status
                $wahaStatus = $this->waitForSessionStatus($sessionId, ['STARTING', 'SCAN_QR_CODE', 'WORKING'], 3);
            }
        }
        
        // Determine status based on WAHA status
        $status = $this->mapWahaStatusToDbStatus($wahaStatus);
        
        // Create session in database
        $session = WhatsAppSession::create([
            'user_id' => $user->id,
            'session_name' => $request->name,
            'phone_number' => $phoneNumber,
            'session_id' => $sessionId,
            'status' => $status,
            'waha_instance_url' => config('services.waha.url', 'http://localhost:3000'),
        ]);

        $this->usageService->log($request, 201, $startTime);

        return response()->json([
            'success' => true,
            'message' => 'Device created successfully. Use the pair endpoint to get QR code.',
            'data' => [
                'id' => $session->session_id,
                'name' => $session->session_name,
                'status' => $session->status,
                'created_at' => $session->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get QR code for pairing
     */
    public function pair(Request $request, $sessionId)
    {
        $startTime = microtime(true);
        
        $session = WhatsAppSession::where('session_id', $sessionId)
            ->where('user_id', $request->user->id)
            ->first();

        if (!$session) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device not found',
            ], 404);
        }

        // If already connected
        if ($session->status === 'connected') {
            $this->usageService->log($request, 200, $startTime);
            return response()->json([
                'success' => true,
                'message' => 'Device is already connected',
                'data' => [
                    'id' => $session->session_id,
                    'status' => $session->status,
                    'is_connected' => true,
                ],
            ]);
        }

        // Check WAHA session status
        $statusResult = $this->wahaService->getSessionStatus($session->session_id);
        
        // If session is not in SCAN_QR_CODE state, try to restart it
        if ($statusResult['success'] && $statusResult['status'] !== 'SCAN_QR_CODE' && $statusResult['status'] !== 'WORKING') {
            \Log::info('SessionApiController: Session not in QR state, restarting', [
                'current_status' => $statusResult['status'],
            ]);
            
            $this->wahaService->stopSession($session->session_id);
            sleep(2);
            $this->wahaService->createSession($session->session_id);
            sleep(3);
            
            $statusResult = $this->wahaService->getSessionStatus($session->session_id);
        }
        
        // Try to get fresh QR code
        $maxAttempts = 5;
        $qrResult = null;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            if ($i > 0) {
                sleep(2);
            }
            
            $qrResult = $this->wahaService->getQrCode($session->session_id);
            
            if ($qrResult['success'] && !empty($qrResult['qr_code'])) {
                break;
            }
        }
        
        if ($qrResult && $qrResult['success'] && !empty($qrResult['qr_code'])) {
            $session->update([
                'qr_code' => $qrResult['qr_code'],
                'qr_code_expires_at' => $qrResult['expires_at'] ?? now()->addMinutes(2),
                'status' => 'pairing',
            ]);
            
            $this->usageService->log($request, 200, $startTime);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $session->session_id,
                    'name' => $session->session_name,
                    'status' => $session->status,
                    'qr_code' => $qrResult['qr_code'],
                    'qr_code_expires_at' => $session->qr_code_expires_at->toIso8601String(),
                    'is_connected' => false,
                ],
            ]);
        }

        $this->usageService->log($request, 500, $startTime);
        
        return response()->json([
            'success' => false,
            'error' => $qrResult['error'] ?? 'Failed to get QR code. Please try again.',
        ], 500);
    }

    /**
     * Wait for session to reach one of the target statuses with polling.
     */
    protected function waitForSessionStatus(string $sessionId, array $targetStatuses, int $maxWaitSeconds = 3): string
    {
        $startTime = time();
        $checkInterval = 0.3;
        
        while ((time() - $startTime) < $maxWaitSeconds) {
            $statusResult = $this->wahaService->getSessionStatus($sessionId);
            $currentStatus = $statusResult['status'] ?? 'unknown';
            
            if (in_array($currentStatus, $targetStatuses)) {
                return $currentStatus;
            }
            
            usleep($checkInterval * 1000000);
        }
        
        $statusResult = $this->wahaService->getSessionStatus($sessionId);
        return $statusResult['status'] ?? 'unknown';
    }

    /**
     * Map WAHA status to database status.
     */
    protected function mapWahaStatusToDbStatus(?string $wahaStatus): string
    {
        return match($wahaStatus) {
            'WORKING' => 'connected',
            'SCAN_QR_CODE', 'STARTING' => 'pairing',
            'STOPPED', 'FAILED' => 'disconnected',
            default => 'pairing',
        };
    }

    /**
     * Get user's session limit based on subscription plan.
     */
    protected function getUserSessionsLimit($user): int
    {
        $activeSubscription = $user->activeSubscription;
        if ($activeSubscription && $activeSubscription->plan) {
            return $activeSubscription->plan->sessions_limit;
        }

        if ($user->subscriptionPlan) {
            return $user->subscriptionPlan->sessions_limit;
        }

        return 10; // Default limit
    }
}


