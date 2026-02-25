<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppSession;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class SessionController extends Controller
{
    protected WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->middleware('auth');
        $this->wahaService = $wahaService;
    }

    /**
     * Display a listing of sessions.
     */
    public function index()
    {
        $sessions = Auth::user()->whatsappSessions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('sessions.index', compact('sessions'));
    }

    /**
     * Show the form for creating a new session.
     */
    public function create()
    {
        return view('sessions.create');
    }

    /**
     * Store a newly created session.
     * Optimized for performance - reduced sleep calls and improved flow.
     */
    public function store(Request $request)
    {
        $startTime = microtime(true);
        
        // Security: Validate request
        $request->validate([
            'session_name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'regex:/^08[0-9]{8,11}$/',
            ],
        ], [
            'phone_number.required' => 'Nomor telepon wajib diisi.',
            'phone_number.regex' => 'Format nomor telepon tidak valid. Gunakan format: 081395777706 (10-13 digit, dimulai dengan 08).',
        ]);

        $user = Auth::user();
        
        // Normalize phone number using PhoneNumberHelper (convert to +62 format for database)
        $phoneNumber = \App\Helpers\PhoneNumberHelper::normalize($request->phone_number);
        
        if (!$phoneNumber) {
            return back()->withErrors([
                'phone_number' => 'Format nomor telepon tidak valid. Gunakan format: 081395777706',
            ])->withInput();
        }
        
        // WAHA Plus supports multiple sessions - check subscription plan limit
        $sessionsLimit = $this->getUserSessionsLimit($user);
        $activeSessionsCount = $user->whatsappSessions()
            ->whereIn('status', ['pairing', 'connected'])
            ->count();
        
        if ($activeSessionsCount >= $sessionsLimit) {
            return back()->withErrors([
                'error' => "You have reached your session limit ({$sessionsLimit}). Please delete an existing session first or upgrade your plan."
            ]);
        }
        
        // WAHA Plus supports multiple sessions - use UUID for session_id
        $sessionId = (string) Str::uuid();

        \Log::info('SessionController: Starting session creation', [
            'user_id' => Auth::id(),
            'session_id' => $sessionId,
            'phone_number' => $phoneNumber,
        ]);

        // Check if session already exists in WAHA (optimized - single check)
        $statusResult = $this->wahaService->getSessionStatus($sessionId);
        $sessionExistsInWaha = $statusResult['success'];
        $wahaStatus = $statusResult['status'] ?? null;
        
        // Only stop and restart if session exists and is in bad state
        if ($sessionExistsInWaha && !in_array($wahaStatus, ['SCAN_QR_CODE', 'WORKING', 'STARTING'])) {
            \Log::info('SessionController: Stopping existing session in bad state', [
                'current_status' => $wahaStatus,
            ]);
            $this->wahaService->stopSession($sessionId);
            // Reduced wait time - use polling instead of fixed sleep
            usleep(500000); // 0.5 seconds instead of 2 seconds
        }
        
        // Create session in WAHA if not exists or needs restart
        if (!$sessionExistsInWaha || !in_array($wahaStatus, ['SCAN_QR_CODE', 'WORKING', 'STARTING'])) {
            $wahaResult = $this->wahaService->createSession($sessionId);
            
            // Check if WAHA Core was used (fallback to 'default')
            $wahaSessionName = $wahaResult['waha_session_name'] ?? $sessionId;
            $actualSessionId = $wahaResult['original_session_id'] ?? $sessionId;
            
            if (!$wahaResult['success']) {
                $errorMsg = $wahaResult['error'] ?? '';
                // Handle "already exists" gracefully
                if (str_contains($errorMsg, 'already started') || str_contains($errorMsg, 'already exists')) {
                    \Log::info('SessionController: Session already exists, rechecking status');
                    // Check status using actual WAHA session name (might be 'default')
                    $checkSessionId = $wahaSessionName === 'default' ? 'default' : $sessionId;
                    $statusResult = $this->wahaService->getSessionStatus($checkSessionId);
                    $wahaStatus = $statusResult['status'] ?? 'unknown';
                } else {
                    \Log::error('SessionController: Failed to create session', [
                        'error' => $wahaResult['error'],
                    ]);
                    return back()->withErrors(['error' => $wahaResult['error'] ?? 'Failed to create session']);
                }
            } else {
                // Optimized: Poll for status instead of fixed sleep
                // Use actual WAHA session name for status check (might be 'default' for WAHA Core)
                $checkSessionId = $wahaSessionName === 'default' ? 'default' : $sessionId;
                $wahaStatus = $this->waitForSessionStatus($checkSessionId, ['STARTING', 'SCAN_QR_CODE', 'WORKING'], 3);
                
                if ($wahaSessionName === 'default') {
                    \Log::info('SessionController: Using WAHA Core with default session', [
                        'database_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                    ]);
                }
            }
        }
        
        // Determine status based on WAHA status
        $status = $this->mapWahaStatusToDbStatus($wahaStatus);
        
        // Create session in database immediately (don't wait for QR)
        $session = WhatsAppSession::create([
            'user_id' => Auth::id(),
            'session_name' => $request->session_name,
            'phone_number' => $phoneNumber,
            'session_id' => $sessionId,
            'status' => $status,
            'waha_instance_url' => config('services.waha.url', 'http://localhost:3000'),
        ]);

        // Optimized: Don't wait for QR code here - fetch it asynchronously in pair() method
        // This reduces response time significantly
        \Log::info('SessionController: Session created, redirecting to pair page', [
            'session_id' => $sessionId,
            'status' => $status,
            'execution_time' => number_format(microtime(true) - $startTime, 2) . 's',
        ]);

        return redirect()->route('sessions.pair', $session->id)
            ->with('success', 'Session created successfully. Please scan the QR code to pair.');
    }

    /**
     * Wait for session to reach one of the target statuses with polling.
     * Returns the final status or 'unknown' if timeout.
     */
    protected function waitForSessionStatus(string $sessionId, array $targetStatuses, int $maxWaitSeconds = 3): string
    {
        $startTime = time();
        $checkInterval = 0.3; // Check every 300ms
        
        while ((time() - $startTime) < $maxWaitSeconds) {
            $statusResult = $this->wahaService->getSessionStatus($sessionId);
            $currentStatus = $statusResult['status'] ?? 'unknown';
            
            if (in_array($currentStatus, $targetStatuses)) {
                return $currentStatus;
            }
            
            usleep($checkInterval * 1000000); // Convert to microseconds
        }
        
        // Final check
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
     * Returns the limit from active subscription plan, or default limit for development.
     */
    protected function getUserSessionsLimit($user): int
    {
        // Check active subscription first
        $activeSubscription = $user->activeSubscription;
        if ($activeSubscription && $activeSubscription->plan) {
            $limit = $activeSubscription->plan->sessions_limit;
            \Log::info('SessionController: Using subscription plan limit', [
                'user_id' => $user->id,
                'plan' => $activeSubscription->plan->name,
                'limit' => $limit,
            ]);
            return $limit;
        }

        // Check subscription plan from user (direct relationship)
        if ($user->subscriptionPlan) {
            $limit = $user->subscriptionPlan->sessions_limit;
            \Log::info('SessionController: Using user plan limit', [
                'user_id' => $user->id,
                'plan' => $user->subscriptionPlan->name,
                'limit' => $limit,
            ]);
            return $limit;
        }

        // Default: For WAHA Plus, allow multiple sessions (set to 10 for development)
        // In production, users should have a subscription plan
        $defaultLimit = 10; // WAHA Plus supports unlimited, but we set reasonable default
        \Log::info('SessionController: Using default limit (no subscription)', [
            'user_id' => $user->id,
            'default_limit' => $defaultLimit,
        ]);
        return $defaultLimit;
    }

    /**
     * Display the specified session.
     */
    public function show(WhatsAppSession $session)
    {
        $this->authorize('view', $session);

        // Refresh status from WAHA
        $statusResult = $this->wahaService->getSessionStatus($session->session_id);
        
        if ($statusResult['success']) {
            $session->update([
                'status' => $statusResult['status'] === 'WORKING' ? 'connected' : 'disconnected',
                'last_activity_at' => now(),
            ]);
        }

        return view('sessions.show', compact('session'));
    }

    /**
     * Update the specified session.
     */
    public function update(Request $request, WhatsAppSession $session)
    {
        $this->authorize('update', $session);

        $request->validate([
            'session_name' => 'required|string|max:255',
        ]);

        $session->update([
            'session_name' => $request->session_name,
        ]);

        // Always return JSON if Accept header is application/json or if it's an AJAX request
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Nama session berhasil diperbarui.',
                'session_name' => $session->session_name,
            ]);
        }

        return back()->with('success', 'Nama session berhasil diperbarui.');
    }

    /**
     * Show QR code for pairing.
     */
    public function pair(WhatsAppSession $session)
    {
        // Increase execution time limit to 2 minutes for QR code generation
        set_time_limit(120);
        
        $this->authorize('view', $session);

        // If already connected, redirect to show page
        if ($session->status === 'connected') {
            return redirect()->route('sessions.show', $session)
                ->with('success', 'Session is already connected.');
        }

        // Check WAHA session status first
        $statusResult = $this->wahaService->getSessionStatus($session->session_id);
        \Log::info('SessionController: Pair page - WAHA status', [
            'session_id' => $session->session_id,
            'waha_status' => $statusResult['status'] ?? 'unknown',
            'waha_data' => $statusResult['data'] ?? [],
        ]);
        
        // If session is not in SCAN_QR_CODE state, try to restart it
        if ($statusResult['success'] && $statusResult['status'] !== 'SCAN_QR_CODE' && $statusResult['status'] !== 'WORKING') {
            \Log::info('SessionController: Session not in QR state, restarting', [
                'current_status' => $statusResult['status'],
            ]);
            
            // Stop and restart session
            $this->wahaService->stopSession($session->session_id);
            sleep(2);
            $this->wahaService->createSession($session->session_id);
            sleep(3);
            
            // Re-check status
            $statusResult = $this->wahaService->getSessionStatus($session->session_id);
        }
        
        // Always try to get fresh QR code (QR codes expire quickly)
        \Log::info('SessionController: Fetching fresh QR code', [
            'session_id' => $session->session_id,
            'has_qr' => !empty($session->qr_code),
            'is_expired' => $session->isQrCodeExpired(),
            'waha_status' => $statusResult['status'] ?? 'unknown',
        ]);
        
        // Try multiple times with delay to get fresh QR code
        $maxAttempts = 5;
        $qrResult = null;
        
        for ($i = 0; $i < $maxAttempts; $i++) {
            if ($i > 0) {
                sleep(2); // Wait 2 seconds between attempts
            }
            
            \Log::debug('SessionController: QR code fetch attempt', [
                'attempt' => $i + 1,
                'max_attempts' => $maxAttempts,
            ]);
            
            $qrResult = $this->wahaService->getQrCode($session->session_id);
            
            if ($qrResult['success'] && !empty($qrResult['qr_code'])) {
                \Log::info('SessionController: QR code retrieved successfully', [
                    'attempt' => $i + 1,
                    'qr_code_length' => strlen($qrResult['qr_code']),
                ]);
                break;
            } else {
                \Log::warning('SessionController: QR code fetch failed', [
                    'attempt' => $i + 1,
                    'error' => $qrResult['error'] ?? 'Unknown',
                ]);
            }
        }
        
        if ($qrResult && $qrResult['success'] && !empty($qrResult['qr_code'])) {
            $session->update([
                'qr_code' => $qrResult['qr_code'],
                'qr_code_expires_at' => $qrResult['expires_at'] ?? now()->addMinutes(2),
                'status' => 'pairing',
            ]);
            \Log::info('SessionController: QR code saved to database');
        } else {
            \Log::error('SessionController: Failed to get QR code after multiple attempts', [
                'session_id' => $session->session_id,
                'error' => $qrResult['error'] ?? 'Unknown error',
                'attempts' => $maxAttempts,
            ]);
        }

        // Refresh session to get latest data
        $session->refresh();
        
        \Log::info('SessionController: Pair page ready', [
            'session_id' => $session->session_id,
            'has_qr_code' => !empty($session->qr_code),
            'status' => $session->status,
        ]);

        return view('sessions.pair', compact('session'));
    }

    /**
     * Check pairing status (AJAX endpoint).
     */
    /**
     * Update webhook configuration for connected session
     */
    public function updateWebhook(WhatsAppSession $session)
    {
        $this->authorize('view', $session);

        if ($session->status !== 'connected') {
            return response()->json([
                'success' => false,
                'message' => 'Session harus dalam status connected untuk update webhook',
            ], 400);
        }

        try {
            $result = $this->wahaService->updateWebhook($session->session_id);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook berhasil diupdate. Session akan restart untuk menerapkan perubahan.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal update webhook: ' . ($result['error'] ?? 'Unknown error'),
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Error updating webhook', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkStatus(WhatsAppSession $session)
    {
        $this->authorize('view', $session);

        $statusResult = $this->wahaService->getSessionStatus($session->session_id);

        if (!$statusResult['success']) {
            $error = $statusResult['error'] ?? 'Unknown error';
            
            // If session not found in WAHA, try to create it automatically
            if (stripos($error, 'Session not found') !== false || stripos($error, '404') !== false) {
                \Log::info('SessionController: Session not found in WAHA, attempting automatic recreation', [
                    'session_id' => $session->session_id
                ]);
                
                $createResult = $this->wahaService->createSession($session->session_id);
                
                if ($createResult['success']) {
                    // Try to get status again after creation
                    $statusResult = $this->wahaService->getSessionStatus($session->session_id);
                } else {
                    \Log::error('SessionController: Automatic recreation failed', [
                        'session_id' => $session->session_id,
                        'error' => $createResult['error'] ?? 'Unknown error'
                    ]);
                    
                    return response()->json([
                        'status' => $session->status,
                        'is_connected' => false,
                        'error' => $createResult['error'] ?? 'Failed to recreate session in WAHA',
                    ], 500);
                }
            } else {
                return response()->json([
                    'status' => $session->status,
                    'is_connected' => false,
                    'error' => $error,
                ], 500);
            }
        }

        // Now process statusResult (which should be successful if we reached here)
        if ($statusResult['success']) {
            $wahaStatus = $statusResult['status'];
            $newStatus = $wahaStatus === 'WORKING' ? 'connected' : ($wahaStatus === 'SCAN_QR_CODE' ? 'pairing' : 'disconnected');
            
            // Check if status changed to connected
            $wasConnected = $session->status === 'connected';
            $isNowConnected = $newStatus === 'connected';

            \Log::info('SessionController: Status check updated', [
                'session_id' => $session->session_id,
                'waha_status' => $wahaStatus,
                'new_status' => $newStatus,
                'old_status' => $session->status,
            ]);

            $session->update([
                'status' => $newStatus,
                'last_activity_at' => now(),
                'connected_at' => $isNowConnected && !$wasConnected ? now() : $session->connected_at,
            ]);

            // Refresh session to get updated status
            $session->refresh();

            return response()->json([
                'status' => $session->status,
                'is_connected' => $session->isConnected() || $isNowConnected,
                'waha_status' => $wahaStatus,
                'status_changed' => $wasConnected !== $isNowConnected,
            ]);
        }

    }

    /**
     * Refresh QR code (AJAX endpoint).
     */
    public function refreshQrCode(WhatsAppSession $session)
    {
        $this->authorize('view', $session);

        \Log::info('SessionController: Refreshing QR code', ['session_id' => $session->session_id]);

        // Get fresh QR code
        $qrResult = $this->wahaService->getQrCode($session->session_id);

        if ($qrResult['success'] && !empty($qrResult['qr_code'])) {
            $session->update([
                'qr_code' => $qrResult['qr_code'],
                'qr_code_expires_at' => $qrResult['expires_at'] ?? now()->addMinutes(2),
                'status' => 'pairing',
            ]);

            return response()->json([
                'success' => true,
                'qr_code' => $qrResult['qr_code'],
                'expires_at' => $session->qr_code_expires_at->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $qrResult['error'] ?? 'Failed to get QR code',
        ], 500);
    }

    /**
     * Stop a session.
     */
    public function stop(WhatsAppSession $session)
    {
        $this->authorize('update', $session);

        $result = $this->wahaService->stopSession($session->session_id);

        if ($result['success']) {
            $session->update([
                'status' => 'disconnected',
                'disconnected_at' => now(),
            ]);

            return back()->with('success', 'Session stopped successfully.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Failed to stop session']);
    }

    /**
     * Remove the specified session.
     */
    public function destroy(WhatsAppSession $session)
    {
        $this->authorize('delete', $session);

        $sessionId = $session->session_id;
        $sessionDbId = $session->id;

        \Log::info('SessionController: Deleting session and all related data', [
            'session_id' => $sessionId,
            'session_db_id' => $sessionDbId,
            'session_name' => $session->session_name,
        ]);

        // Delete all messages (incoming and outgoing) for this session
        $messagesCount = $session->messages()->count();
        $session->messages()->delete();
        \Log::info('SessionController: Deleted messages', [
            'session_id' => $sessionId,
            'messages_deleted' => $messagesCount,
        ]);

        // Delete all webhooks for this session
        $webhooksCount = $session->webhooks()->count();
        $session->webhooks()->delete();
        \Log::info('SessionController: Deleted webhooks', [
            'session_id' => $sessionId,
            'webhooks_deleted' => $webhooksCount,
        ]);

        // Delete from WAHA
        $this->wahaService->deleteSession($sessionId);

        // Delete from database (this will cascade delete related records if foreign keys are set)
        $session->delete();

        \Log::info('SessionController: Session deleted successfully', [
            'session_id' => $sessionId,
            'total_messages_deleted' => $messagesCount,
            'total_webhooks_deleted' => $webhooksCount,
        ]);

        return redirect()->route('sessions.index')
            ->with('success', 'Session dan semua data terkait (pesan masuk, pesan keluar, dan webhook) telah dihapus.');
    }
}
