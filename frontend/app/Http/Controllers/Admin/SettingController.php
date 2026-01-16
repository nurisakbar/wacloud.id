<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\EnsureUserIsAdmin::class);
    }

    /**
     * Display settings page
     */
    public function index()
    {
        $notificationApiKey = Setting::getValue('notification_api_key', '');
        $notificationDeviceId = Setting::getValue('notification_device_id', '');
        $notificationBaseUrl = Setting::getValue('notification_base_url', '');
        
        return view('admin.settings.index', compact(
            'notificationApiKey', 
            'notificationDeviceId',
            'notificationBaseUrl'
        ));
    }

    /**
     * Get quota statistics from WACloud API
     */
    public function getQuotaStats()
    {
        // Get settings from database only (no fallback to .env)
        $apiKey = Setting::getValue('notification_api_key');
        $baseUrl = Setting::getValue('notification_base_url');

        // Validate required fields
        if (empty($apiKey) || empty($baseUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'API Key dan Base URL harus dikonfigurasi terlebih dahulu',
                'message' => 'Silakan isi API Key dan Base URL di pengaturan notifikasi',
            ], 400);
        }

        try {
            // Call WACloud API to get account/quota information
            $url = rtrim($baseUrl, '/') . '/account';
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('WACloud quota stats fetched successfully', [
                    'base_url' => $baseUrl,
                    'status_code' => $response->status(),
                ]);

                // Extract quota data from WACloud API response
                // Adjust based on actual WACloud API response structure
                $data = $responseData['data'] ?? $responseData;
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_balance' => (float) ($data['balance'] ?? $data['quota']['balance'] ?? 0),
                        'total_text_quota' => (int) ($data['text_quota'] ?? $data['quota']['text_quota'] ?? 0),
                        'total_multimedia_quota' => (int) ($data['multimedia_quota'] ?? $data['quota']['multimedia_quota'] ?? 0),
                        'total_free_text_quota' => (int) ($data['free_text_quota'] ?? $data['quota']['free_text_quota'] ?? 0),
                        'raw_response' => $responseData, // Include raw response for debugging
                    ],
                ]);
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Unknown error';
                
                Log::warning('WACloud quota stats fetch failed', [
                    'base_url' => $baseUrl,
                    'status_code' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Gagal mengambil data quota dari WACloud',
                    'message' => $errorMessage,
                    'details' => [
                        'status_code' => $response->status(),
                        'base_url' => $baseUrl,
                        'response' => $errorData,
                    ],
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('WACloud quota stats error: ' . $e->getMessage(), [
                'base_url' => $baseUrl,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat mengambil data quota',
                'message' => $e->getMessage(),
                'details' => [
                    'base_url' => $baseUrl,
                ],
            ], 500);
        }
    }

    /**
     * Test connection to WACloud notification service
     */
    public function testConnection(Request $request)
    {
        $apiKey = $request->input('api_key');
        $deviceId = $request->input('device_id');
        $baseUrl = $request->input('base_url');

        // Use provided values or get from settings/database (no fallback to .env)
        if (empty($apiKey)) {
            $apiKey = Setting::getValue('notification_api_key');
        }
        if (empty($deviceId)) {
            $deviceId = Setting::getValue('notification_device_id');
        }
        if (empty($baseUrl)) {
            $baseUrl = Setting::getValue('notification_base_url');
        }

        // Validate required fields
        if (empty($apiKey) || empty($deviceId) || empty($baseUrl)) {
            return response()->json([
                'success' => false,
                'error' => 'API Key, Device ID, dan Base URL harus diisi',
                'details' => [
                    'api_key_set' => !empty($apiKey),
                    'device_id_set' => !empty($deviceId),
                    'base_url_set' => !empty($baseUrl),
                ],
            ], 400);
        }

        try {
            // Try to make a test request to WACloud API
            // Try multiple endpoints: device status first, then device info
            $response = null;
            $testedUrl = '';
            
            // First try: Get device status (most reliable)
            $url = rtrim($baseUrl, '/') . '/devices/' . $deviceId . '/status';
            $testedUrl = $url;
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(10)
            ->get($url);

            // If status endpoint doesn't work, try device info endpoint
            if (!$response->successful()) {
                $url = rtrim($baseUrl, '/') . '/devices/' . $deviceId;
                $testedUrl = $url;
                
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(10)
                ->get($url);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WACloud connection test successful', [
                    'base_url' => $baseUrl,
                    'device_id' => $deviceId,
                    'tested_url' => $testedUrl,
                    'status_code' => $response->status(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil! Konfigurasi valid.',
                    'details' => [
                        'status_code' => $response->status(),
                        'device_id' => $deviceId,
                        'base_url' => $baseUrl,
                        'response' => $responseData,
                    ],
                ]);
            } else {
                $errorData = $response->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Unknown error';
                
                Log::warning('WACloud connection test failed', [
                    'base_url' => $baseUrl,
                    'device_id' => $deviceId,
                    'tested_url' => $testedUrl,
                    'status_code' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Koneksi gagal',
                    'message' => $errorMessage,
                    'details' => [
                        'status_code' => $response->status(),
                        'device_id' => $deviceId,
                        'base_url' => $baseUrl,
                        'response' => $errorData,
                    ],
                ], $response->status());
            }
        } catch (\Exception $e) {
            Log::error('WACloud connection test error: ' . $e->getMessage(), [
                'base_url' => $baseUrl,
                'device_id' => $deviceId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Terjadi kesalahan saat menguji koneksi',
                'message' => $e->getMessage(),
                'details' => [
                    'base_url' => $baseUrl,
                    'device_id' => $deviceId,
                ],
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'notification_api_key' => 'nullable|string|max:255',
            'notification_device_id' => 'nullable|string|max:255',
            'notification_base_url' => 'nullable|url|max:255',
        ]);

        Setting::setValue(
            'notification_api_key',
            $request->notification_api_key ?? '',
            'API Key untuk mengirim notifikasi ke client (WACloud)'
        );

        Setting::setValue(
            'notification_device_id',
            $request->notification_device_id ?? '',
            'Device ID untuk mengirim notifikasi ke client (WACloud)'
        );

        Setting::setValue(
            'notification_base_url',
            $request->notification_base_url ?? '',
            'Base URL untuk mengirim notifikasi ke client (WACloud)'
        );

        Log::info('Settings updated', [
            'admin_id' => auth()->id(),
            'updated_keys' => ['notification_api_key', 'notification_device_id', 'notification_base_url'],
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengaturan berhasil diperbarui!');
    }
}

