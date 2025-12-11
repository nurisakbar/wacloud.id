<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppSession;
use App\Services\ApiUsageService;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactApiController extends Controller
{
    protected ApiUsageService $usageService;
    protected WahaService $wahaService;

    public function __construct(ApiUsageService $usageService, WahaService $wahaService)
    {
        $this->usageService = $usageService;
        $this->wahaService = $wahaService;
    }

    /**
     * Get all contacts for a session with pagination.
     * GET /api/v1/devices/{session}/contacts
     */
    public function index(Request $request, $sessionId)
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

        $limit = (int) $request->get('limit', 100);
        $offset = (int) $request->get('offset', 0);
        $sortBy = $request->get('sortBy', 'id');
        $sortOrder = $request->get('sortOrder', 'asc');

        $result = $this->wahaService->getAllContacts(
            $sessionId,
            $limit,
            $offset,
            $sortBy,
            $sortOrder
        );

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to get contacts',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get a single contact by ID.
     * GET /api/v1/devices/{session}/contacts/{contactId}
     */
    public function show(Request $request, $sessionId, $contactId)
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

        $result = $this->wahaService->getContact($sessionId, $contactId);

        if (!$result['success']) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Contact not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Update contact information.
     * PUT /api/v1/devices/{session}/contacts/{chatId}
     */
    public function update(Request $request, $sessionId, $chatId)
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

        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $this->usageService->log($request, 422, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->wahaService->updateContact(
            $sessionId,
            $chatId,
            $request->firstName,
            $request->lastName
        );

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to update contact',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Check if phone number exists in WhatsApp.
     * GET /api/v1/devices/{session}/contacts/check-exists
     */
    public function checkExists(Request $request, $sessionId)
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

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{9,15}$/',
        ]);

        if ($validator->fails()) {
            $this->usageService->log($request, 422, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->wahaService->checkPhoneExists($sessionId, $request->phone);

        if (!$result['success']) {
            $statusCode = 500;
            // If it's a validation error from WAHA, use 400
            if (str_contains(strtolower($result['error'] ?? ''), 'validation') || 
                str_contains(strtolower($result['error'] ?? ''), 'invalid')) {
                $statusCode = 400;
            }
            
            $this->usageService->log($request, $statusCode, $startTime);
            
            Log::error('ContactApiController: check-exists failed', [
                'session_id' => $sessionId,
                'phone' => $request->phone,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to check phone number',
            ], $statusCode);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get contact "about" information.
     * GET /api/v1/devices/{session}/contacts/{contactId}/about
     */
    public function about(Request $request, $sessionId, $contactId)
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

        $result = $this->wahaService->getContactAbout($sessionId, $contactId);

        if (!$result['success']) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Contact about not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get contact profile picture.
     * GET /api/v1/devices/{session}/contacts/{contactId}/profile-picture
     */
    public function profilePicture(Request $request, $sessionId, $contactId)
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

        $refresh = $request->get('refresh', 'false') === 'true' || $request->get('refresh') === true;

        $result = $this->wahaService->getContactProfilePicture($sessionId, $contactId, $refresh);

        if (!$result['success']) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Profile picture not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Block a contact.
     * POST /api/v1/devices/{session}/contacts/{contactId}/block
     */
    public function block(Request $request, $sessionId, $contactId)
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

        $result = $this->wahaService->blockContact($sessionId, $contactId);

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to block contact',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Unblock a contact.
     * POST /api/v1/devices/{session}/contacts/{contactId}/unblock
     */
    public function unblock(Request $request, $sessionId, $contactId)
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

        $result = $this->wahaService->unblockContact($sessionId, $contactId);

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to unblock contact',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get all known LIDs (Linked IDs) for a session.
     * GET /api/v1/devices/{session}/lids
     */
    public function lids(Request $request, $sessionId)
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

        $limit = (int) $request->get('limit', 100);
        $offset = (int) $request->get('offset', 0);

        $result = $this->wahaService->getAllLids($sessionId, $limit, $offset);

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to get LIDs',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get count of LIDs for a session.
     * GET /api/v1/devices/{session}/lids/count
     */
    public function lidsCount(Request $request, $sessionId)
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

        $result = $this->wahaService->getLidsCount($sessionId);

        if (!$result['success']) {
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to get LIDs count',
            ], 500);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get phone number by LID.
     * GET /api/v1/devices/{session}/lids/{lid}
     */
    public function phoneByLid(Request $request, $sessionId, $lid)
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

        $result = $this->wahaService->getPhoneByLid($sessionId, $lid);

        if (!$result['success']) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'LID not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Get LID by phone number.
     * GET /api/v1/devices/{session}/lids/phone/{phoneNumber}
     */
    public function lidByPhone(Request $request, $sessionId, $phoneNumber)
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

        $result = $this->wahaService->getLidByPhone($sessionId, $phoneNumber);

        if (!$result['success']) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Phone number not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }
}

