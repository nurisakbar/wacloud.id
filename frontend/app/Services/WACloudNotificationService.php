<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WACloudNotificationService
{
    protected $apiKey;
    protected $baseUrl;
    protected $deviceId;

    public function __construct()
    {
        $this->apiKey = config('services.wacloud.api_key');
        $this->baseUrl = config('services.wacloud.base_url', 'https://app.wacloud.id/api/v1');
        $this->deviceId = config('services.wacloud.device_id');
        
        // Log configuration for debugging
        Log::info('WACloudNotificationService initialized', [
            'api_key_set' => !empty($this->apiKey),
            'device_id_set' => !empty($this->deviceId),
            'base_url' => $this->baseUrl,
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
            'device_id' => $this->deviceId,
        ]);
        
        if (empty($this->apiKey) || empty($this->deviceId)) {
            Log::warning('WACloud notification skipped: API key or device ID not configured', [
                'api_key_set' => !empty($this->apiKey),
                'device_id_set' => !empty($this->deviceId),
            ]);
            return [
                'success' => false,
                'error' => 'WACloud not configured'
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
            $url = rtrim($this->baseUrl, '/') . '/messages';
            
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
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
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

