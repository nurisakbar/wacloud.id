<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WACloudNotificationService
{
    protected $apiKey;
    protected $baseUrl;
    protected $deviceId;

    public function __construct()
    {
        // Get from database settings only (no fallback to .env)
        $this->apiKey = Setting::getValue('notification_api_key');
        $this->baseUrl = Setting::getValue('notification_base_url');
        $this->deviceId = Setting::getValue('notification_device_id');
        
        // Log configuration for debugging
        Log::info('WACloudNotificationService initialized', [
            'api_key_set' => !empty($this->apiKey),
            'device_id_set' => !empty($this->deviceId),
            'base_url' => $this->baseUrl,
            'source' => 'database',
        ]);
    }

    /**
     * Send WhatsApp notification
     *
     * @param string $phoneNumber Phone number in international format (e.g., 6281234567890)
     * @param string $message Message text
     * @return array
     */
    public function sendNotification($phoneNumber, $message)
    {
        Log::info('WACloudNotificationService::sendNotification called', [
            'phone_number' => $phoneNumber,
            'message_length' => strlen($message),
            'api_key_set' => !empty($this->apiKey),
            'device_id_set' => !empty($this->deviceId),
            'base_url_set' => !empty($this->baseUrl),
            'base_url' => $this->baseUrl,
            'device_id' => $this->deviceId,
        ]);
        
        // Validate required configuration
        if (empty($this->apiKey)) {
            Log::warning('WACloud notification skipped: API key not configured', [
                'api_key_set' => false,
            ]);
            return [
                'success' => false,
                'error' => 'WACloud API key tidak dikonfigurasi. Silakan isi di halaman Settings.'
            ];
        }
        
        if (empty($this->deviceId)) {
            Log::warning('WACloud notification skipped: Device ID not configured', [
                'device_id_set' => false,
            ]);
            return [
                'success' => false,
                'error' => 'WACloud Device ID tidak dikonfigurasi. Silakan isi di halaman Settings.'
            ];
        }
        
        if (empty($this->baseUrl)) {
            Log::warning('WACloud notification skipped: Base URL not configured', [
                'base_url_set' => false,
            ]);
            return [
                'success' => false,
                'error' => 'WACloud Base URL tidak dikonfigurasi. Silakan isi di halaman Settings.'
            ];
        }

        // Format phone number (remove +, spaces, dashes)
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Ensure phone number starts with country code (62 for Indonesia)
        if (substr($phoneNumber, 0, 2) !== '62') {
            // If starts with 0, replace with 62
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '62' . substr($phoneNumber, 1);
            } else {
                // Assume it's missing country code, add 62
                $phoneNumber = '62' . $phoneNumber;
            }
        }

        try {
            // Ensure base URL is valid and construct full URL
            $baseUrl = rtrim($this->baseUrl, '/');
            if (empty($baseUrl)) {
                return [
                    'success' => false,
                    'error' => 'Base URL tidak valid. Silakan periksa konfigurasi di halaman Settings.'
                ];
            }
            
            $url = $baseUrl . '/messages';
            
            $payload = [
                'device_id' => $this->deviceId,
                'to' => $phoneNumber,
                'message_type' => 'text',
                'text' => $message,
            ];

            Log::info('WACloud: Sending notification', [
                'url' => $url,
                'device_id' => $this->deviceId,
                'to' => $phoneNumber,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(30)
            ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WACloud: Notification sent successfully', [
                    'response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Failed to send notification';
            
            Log::error('WACloud: Notification failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'api_key_set' => !empty($this->apiKey),
                'api_key_length' => $this->apiKey ? strlen($this->apiKey) : 0,
                'api_key_prefix' => $this->apiKey ? substr($this->apiKey, 0, 8) : 'N/A',
                'device_id' => $this->deviceId,
                'base_url' => $this->baseUrl,
                'url' => $url,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $response->status(),
                'response_data' => $errorData,
            ];
        } catch (\Exception $e) {
            Log::error('WACloud notification error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}

