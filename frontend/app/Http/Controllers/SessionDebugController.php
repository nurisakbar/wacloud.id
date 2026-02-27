<?php

namespace App\Http\Controllers;

use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SessionDebugController extends Controller
{
    protected WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->middleware('auth');
        $this->wahaService = $wahaService;
    }

    /**
     * Debug session information.
     */
    public function debug(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return response()->json(['error' => 'session_id is required'], 400);
        }
        
        $debug = [
            'session_id' => $sessionId,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Check WAHA session status
        $statusResult = $this->wahaService->getSessionStatus($sessionId);
        $debug['waha_status'] = [
            'success' => $statusResult['success'],
            'status' => $statusResult['status'] ?? 'unknown',
            'data' => $statusResult['data'] ?? [],
        ];

        // Try to get QR code
        $qrResult = $this->wahaService->getQrCode($sessionId);
        $debug['qr_code'] = [
            'success' => $qrResult['success'],
            'has_qr' => !empty($qrResult['qr_code']),
            'qr_length' => $qrResult['success'] ? strlen($qrResult['qr_code']) : 0,
            'error' => $qrResult['error'] ?? null,
        ];

        // Check database session
        $dbSession = \App\Models\WhatsAppSession::where('session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->first();
        
        $debug['database'] = [
            'exists' => $dbSession !== null,
            'session_name' => $dbSession->session_name ?? null,
            'status' => $dbSession->status ?? null,
            'has_qr_code' => !empty($dbSession->qr_code ?? null),
            'qr_expires_at' => $dbSession->qr_code_expires_at ?? null,
        ];

        // Check WAHA logs (last 20 lines)
        $wahaLogs = shell_exec("docker logs waha-plus 2>&1 | tail -20");
        $debug['waha_logs'] = explode("\n", trim($wahaLogs));

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Restart session in WAHA.
     */
    public function restart(Request $request)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return response()->json(['error' => 'session_id is required'], 400);
        }
        
        Log::info('SessionDebug: Restarting session', ['session_id' => $sessionId]);
        
        // Stop session
        $stopResult = $this->wahaService->stopSession($sessionId);
        sleep(2);
        
        // Start session
        $startResult = $this->wahaService->createSession($sessionId);
        sleep(3);
        
        // Get new QR code
        $qrResult = $this->wahaService->getQrCode($sessionId);
        
        return response()->json([
            'stop' => $stopResult,
            'start' => $startResult,
            'qr' => $qrResult,
        ], 200, [], JSON_PRETTY_PRINT);
    }
}



