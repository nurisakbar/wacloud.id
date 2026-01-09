<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class WahaService
{
    protected string $baseUrl;
    protected int $timeout;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.waha.url', 'http://localhost:3000');
        $this->timeout = config('services.waha.timeout', 30);
        $this->apiKey = config('services.waha.api_key');
    }

    /**
     * Get base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get HTTP client with authentication headers.
     */
    protected function httpClient(int $timeout = null)
    {
        $timeout = $timeout ?? $this->timeout;
        $client = Http::timeout($timeout);
        
        // WAHA requires API key authentication
        // Try to get from config, or use default from logs
        $apiKey = $this->apiKey ?? env('WAHA_API_KEY');
        
        if ($apiKey) {
            // Mask API key for logging (show first 4 and last 4 chars)
            $maskedKey = strlen($apiKey) > 8 
                ? substr($apiKey, 0, 4) . '...' . substr($apiKey, -4) 
                : '***';
            
            Log::debug('WAHA: Using API key for authentication', [
                'api_key_masked' => $maskedKey,
                'api_key_length' => strlen($apiKey),
                'base_url' => $this->baseUrl,
            ]);
            
            $client->withHeaders([
                'X-Api-Key' => $apiKey,
            ]);
        } else {
            // If no API key configured, try to get from WAHA logs (for development)
            // In production, always set WAHA_API_KEY in .env
            Log::error('WAHA API key not configured. Requests will fail with 401 Unauthorized.', [
                'base_url' => $this->baseUrl,
                'config_api_key' => $this->apiKey ? 'set (from config)' : 'null',
                'env_api_key' => env('WAHA_API_KEY') ? 'set (from env)' : 'null',
                'solution' => 'Set WAHA_API_KEY in .env file to match the API key in docker .env file',
            ]);
        }
        
        return $client;
    }

    /**
     * Create a new WAHA session with retry mechanism.
     */
    public function createSession(string $sessionId, int $maxRetries = 2): array
    {
        $url = "{$this->baseUrl}/api/sessions/start";
        $retryCount = 0;
        
        while ($retryCount <= $maxRetries) {
            try {
                if ($retryCount > 0) {
                    Log::info('WAHA: Retrying session creation', [
                        'session_id' => $sessionId,
                        'attempt' => $retryCount + 1,
                        'max_retries' => $maxRetries,
                    ]);
                    // Exponential backoff: 0.5s, 1s
                    usleep(500000 * $retryCount);
                }
                
                Log::info('WAHA: Creating session', [
                    'session_id' => $sessionId,
                    'url' => $url,
                    'base_url' => $this->baseUrl,
                    'api_key_configured' => !empty($this->apiKey ?? env('WAHA_API_KEY')),
                    'attempt' => $retryCount + 1,
                ]);
                
                // Build payload with optional engine configuration
                $payload = [
                    'name' => $sessionId,
                ];
                
                // Add engine configuration if specified (GOWS supports more features like polls)
                // Engine can also be set via WHATSAPP_DEFAULT_ENGINE environment variable in docker-compose
                $engine = env('WAHA_DEFAULT_ENGINE', 'GOWS');
                
                // ============================================
                // BUILT-IN WEBHOOK CONFIGURATION
                // This webhook is automatically configured to receive messages
                // No manual setup needed - this is the main feature
                // ============================================
                
                // Build webhook URL for receiving messages (BUILT-IN - automatic)
                // Ensure URL has proper format (with port if needed)
                // IMPORTANT: If WAHA runs in Docker, use host.docker.internal instead of localhost
                $appUrl = config('app.url', 'http://localhost:8000');
                
                // Check if WAHA is running in Docker (check if WAHA_URL contains localhost:3000-3002)
                $wahaUrl = config('services.waha.url', 'http://localhost:3000');
                $isWahaInDocker = str_contains($wahaUrl, 'localhost') && 
                                   (str_contains($wahaUrl, ':3000') || 
                                    str_contains($wahaUrl, ':3001') || 
                                    str_contains($wahaUrl, ':3002'));
                
                // If WAHA is in Docker and app URL uses localhost, replace with host.docker.internal
                if ($isWahaInDocker && str_contains($appUrl, 'localhost')) {
                    // Detect OS: macOS/Windows use host.docker.internal, Linux needs host IP
                    $os = strtoupper(PHP_OS);
                    if (str_contains($os, 'DARWIN') || str_contains($os, 'WIN')) {
                        // macOS or Windows - use host.docker.internal
                        $appUrl = str_replace('localhost', 'host.docker.internal', $appUrl);
                        Log::info('WAHA: Using host.docker.internal for webhook (WAHA in Docker)', [
                            'original_url' => config('app.url', 'http://localhost:8000'),
                            'webhook_url' => $appUrl,
                            'os' => $os,
                        ]);
                    } else {
                        // Linux - try to get host IP from environment or use host.docker.internal
                        $hostIp = env('DOCKER_HOST_IP', 'host.docker.internal');
                        $appUrl = str_replace('localhost', $hostIp, $appUrl);
                        Log::info('WAHA: Using host IP for webhook (WAHA in Docker, Linux)', [
                            'original_url' => config('app.url', 'http://localhost:8000'),
                            'webhook_url' => $appUrl,
                            'host_ip' => $hostIp,
                            'os' => $os,
                            'note' => 'Set DOCKER_HOST_IP in .env if host.docker.internal does not work',
                        ]);
                    }
                } else {
                    // Fix: Always add port 8000 for localhost in development
                    if (str_contains($appUrl, 'localhost')) {
                        // Check if port is missing
                        if (!preg_match('/localhost:\d+/', $appUrl)) {
                            $appUrl = 'http://localhost:8000';
                        } elseif (!str_contains($appUrl, ':8000') && str_contains($appUrl, 'localhost')) {
                            // Replace any other port with 8000 for localhost
                            $appUrl = preg_replace('/localhost:\d+/', 'localhost:8000', $appUrl);
                        }
                    }
                }
                
                $webhookUrl = rtrim($appUrl, '/') . '/webhook/receive/' . $sessionId;
                
                Log::info('WAHA: Configuring built-in webhook (automatic - no manual setup needed)', [
                    'session_id' => $sessionId,
                    'webhook_url' => $webhookUrl,
                    'app_url' => config('app.url'),
                    'note' => 'This webhook automatically receives and saves incoming messages to database',
                ]);
                
                // Configure built-in webhooks according to WAHA documentation
                // https://waha.devlike.pro/docs/how-to/receive-messages/
                // https://waha.devlike.pro/docs/how-to/events/
                $payload['config'] = [
                    'engine' => $engine,
                    'webhooks' => [
                        [
                            'url' => $webhookUrl,
                            'events' => [
                                'message',        // Incoming messages - MAIN FEATURE
                                'message.any',    // All messages (incoming and outgoing)
                                'message.ack',    // Message acknowledgments (for status updates)
                                'message.reaction', // Message reactions
                                'message.edited',  // Edited messages
                                'message.revoked', // Revoked messages
                                'session.status',  // Session status changes
                            ],
                        ],
                    ],
                ];
                
                Log::info('WAHA: Built-in webhook configured successfully', [
                    'session_id' => $sessionId,
                    'webhook_url' => $webhookUrl,
                    'events' => $payload['config']['webhooks'][0]['events'],
                    'status' => 'Messages will be automatically received and saved to database',
                ]);
                
                $response = $this->httpClient()
                    ->post($url, $payload);

                if ($response->successful()) {
                    Log::info('WAHA: Create session success with built-in webhook', [
                        'session_id' => $sessionId,
                        'status' => $response->status(),
                        'webhook_url' => $webhookUrl,
                        'note' => 'Built-in webhook is now active - incoming messages will be automatically received and saved',
                    ]);
                    return [
                        'success' => true,
                        'data' => $response->json(),
                    ];
                }

                // Don't retry on 4xx errors (client errors)
                if ($response->status() >= 400 && $response->status() < 500) {
                    $errorMessage = $response->json()['message'] ?? 'Failed to create session';
                    $responseBody = $response->body();
                    
                    // Check if error is about WAHA Core only supporting 'default' session
                    if ($response->status() === 422 && 
                        (stripos($errorMessage, 'WAHA Core support only') !== false || 
                         stripos($errorMessage, 'default session') !== false) &&
                        $sessionId !== 'default') {
                        
                        Log::warning('WAHA Core detected: Retrying with default session name', [
                            'original_session_id' => $sessionId,
                            'retry_with' => 'default',
                            'error' => $errorMessage,
                        ]);
                        
                        // Retry with 'default' session name
                        $originalSessionId = $sessionId;
                        $sessionId = 'default';
                        $payload['name'] = 'default';
                        $url = "{$this->baseUrl}/api/sessions/start";
                        
                        // Update webhook URL to use original session ID for routing
                        // Rebuild webhook URL with Docker-aware logic
                        $webhookUrl = $this->buildWebhookUrl($originalSessionId);
                        $payload['config']['webhooks'][0]['url'] = $webhookUrl;
                        
                        Log::info('WAHA: Retrying session creation with default name', [
                            'original_session_id' => $originalSessionId,
                            'waha_session_name' => 'default',
                            'webhook_url' => $webhookUrl,
                        ]);
                        
                        // Retry the request with 'default' session name
                        $response = $this->httpClient()
                            ->post($url, $payload);
                        
                        if ($response->successful()) {
                            Log::info('WAHA: Create session success with default name (WAHA Core)', [
                                'original_session_id' => $originalSessionId,
                                'waha_session_name' => 'default',
                                'status' => $response->status(),
                                'webhook_url' => $webhookUrl,
                                'note' => 'Using default session name for WAHA Core compatibility',
                            ]);
                            return [
                                'success' => true,
                                'data' => $response->json(),
                                'waha_session_name' => 'default', // Return actual WAHA session name
                                'original_session_id' => $originalSessionId, // Return original for webhook routing
                            ];
                        }
                        
                        // If retry with default also failed, return error
                        $errorMessage = $response->json()['message'] ?? 'Failed to create session even with default name';
                    }
                    
                    Log::error('WAHA create session failed (client error)', [
                        'session_id' => $sessionId,
                        'status' => $response->status(),
                        'error' => $errorMessage,
                        'url' => $url,
                        'base_url' => $this->baseUrl,
                        'api_key_configured' => !empty($this->apiKey ?? env('WAHA_API_KEY')),
                        'response_body' => $responseBody,
                        'solution' => $response->status() === 401 
                            ? 'Check WAHA_API_KEY in Laravel .env matches WAHA_API_KEY in docker .env. Also verify WAHA_API_URL matches WAHA_PORT (e.g., http://localhost:3002)'
                            : 'Check request payload and WAHA API documentation',
                    ]);
                    return [
                        'success' => false,
                        'error' => $errorMessage,
                    ];
                }

                // Retry on 5xx errors or network issues
                $retryCount++;
                if ($retryCount <= $maxRetries) {
                    Log::warning('WAHA create session failed, will retry', [
                        'session_id' => $sessionId,
                        'status' => $response->status(),
                        'retry_count' => $retryCount,
                    ]);
                    continue;
                }

                $errorMessage = $response->json()['message'] ?? 'Failed to create session after retries';
                Log::error('WAHA create session failed after retries', [
                    'session_id' => $sessionId,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                ];
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount > $maxRetries) {
                    Log::error('WAHA create session error after retries: ' . $e->getMessage(), [
                        'session_id' => $sessionId,
                        'exception' => $e->getMessage(),
                    ]);
                    return [
                        'success' => false,
                        'error' => 'Connection error: ' . $e->getMessage(),
                    ];
                }
                Log::warning('WAHA create session exception, will retry', [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                    'retry_count' => $retryCount,
                ]);
            }
        }

        return [
            'success' => false,
            'error' => 'Failed to create session after ' . ($maxRetries + 1) . ' attempts',
        ];
    }

    /**
     * Get QR code for session pairing.
     */
    public function getQrCode(string $sessionId): array
    {
        try {
            Log::info('WAHA: Getting QR code', ['session_id' => $sessionId]);
            
            // Try different possible endpoints (WAHA API endpoints)
            $endpoints = [
                "{$this->baseUrl}/api/{$sessionId}/auth/qr",  // Most common format
                "{$this->baseUrl}/api/sessions/{$sessionId}/auth/qr",
                "{$this->baseUrl}/api/sessions/{$sessionId}/qr",
            ];

            $lastError = null;
            $isWahaCoreError = false;
            
            foreach ($endpoints as $endpoint) {
                try {
                    Log::debug('WAHA: Trying QR endpoint', ['endpoint' => $endpoint]);
                    
                    $response = $this->httpClient()->get($endpoint);
                    
                    Log::debug('WAHA: QR endpoint response', [
                        'endpoint' => $endpoint,
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                        'body_length' => strlen($response->body()),
                    ]);
                    
                    if ($response->successful()) {
                        $contentType = $response->header('Content-Type');
                        
                        // Check if response is PNG image (direct image response)
                        if (str_contains($contentType, 'image/png') || str_contains($contentType, 'image/')) {
                            // Response is PNG image directly - convert to base64
                            $imageData = $response->body();
                            $qrCode = base64_encode($imageData);
                            
                            Log::info('WAHA: QR code retrieved successfully', [
                                'session_id' => $sessionId,
                                'endpoint' => $endpoint,
                                'qr_code_length' => strlen($qrCode),
                            ]);
                            
                            return [
                                'success' => true,
                                'qr_code' => $qrCode,
                                'expires_at' => now()->addMinutes(2), // Default 2 minutes
                            ];
                        }
                        
                        // Try JSON response
                        $data = $response->json();
                        
                        // Handle different response formats
                        $qrCode = $data['qr'] ?? $data['qrcode'] ?? $data['qrCode'] ?? null;
                        
                        if ($qrCode) {
                            // Remove data:image prefix if present
                            $qrCode = preg_replace('/^data:image\/[^;]+;base64,/', '', $qrCode);
                            
                            Log::info('WAHA: QR code retrieved from JSON', [
                                'session_id' => $sessionId,
                                'endpoint' => $endpoint,
                                'qr_code_length' => strlen($qrCode),
                            ]);
                            
                            return [
                                'success' => true,
                                'qr_code' => $qrCode,
                                'expires_at' => isset($data['expiresAt']) 
                                    ? now()->addSeconds($data['expiresAt']) 
                                    : (isset($data['expires_at']) 
                                        ? now()->addSeconds($data['expires_at']) 
                                        : now()->addMinutes(2)),
                            ];
                        }
                    } else {
                        $errorMsg = $response->json()['message'] ?? "HTTP {$response->status()}";
                        $lastError = $errorMsg;
                        
                        // Check if error indicates WAHA Core limitation
                        if ($response->status() === 422 && 
                            (stripos($errorMsg, 'WAHA Core support only') !== false || 
                             stripos($errorMsg, 'default session') !== false) &&
                            $sessionId !== 'default') {
                            $isWahaCoreError = true;
                            Log::warning('WAHA Core detected in QR endpoint: Will retry with default session', [
                                'endpoint' => $endpoint,
                                'original_session_id' => $sessionId,
                                'error' => $errorMsg,
                            ]);
                            break; // Break out of endpoint loop to retry with 'default'
                        }
                        
                        Log::warning('WAHA: QR endpoint failed', [
                            'endpoint' => $endpoint,
                            'status' => $response->status(),
                            'error' => $errorMsg,
                        ]);
                    }
                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::warning('WAHA: QR endpoint exception', [
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // If WAHA Core error detected, retry with 'default' session name
            if ($isWahaCoreError && $sessionId !== 'default') {
                Log::info('WAHA: Retrying QR code fetch with default session name (WAHA Core)', [
                    'original_session_id' => $sessionId,
                    'retry_with' => 'default',
                ]);
                
                // Retry with 'default' session name
                $defaultEndpoints = [
                    "{$this->baseUrl}/api/default/auth/qr",
                    "{$this->baseUrl}/api/sessions/default/auth/qr",
                    "{$this->baseUrl}/api/sessions/default/qr",
                ];
                
                foreach ($defaultEndpoints as $endpoint) {
                    try {
                        Log::debug('WAHA: Trying QR endpoint with default session', ['endpoint' => $endpoint]);
                        
                        $response = $this->httpClient()->get($endpoint);
                        
                        if ($response->successful()) {
                            $contentType = $response->header('Content-Type');
                            
                            // Check if response is PNG image (direct image response)
                            if (str_contains($contentType, 'image/png') || str_contains($contentType, 'image/')) {
                                $imageData = $response->body();
                                $qrCode = base64_encode($imageData);
                                
                                Log::info('WAHA: QR code retrieved successfully with default session (WAHA Core)', [
                                    'original_session_id' => $sessionId,
                                    'waha_session_name' => 'default',
                                    'endpoint' => $endpoint,
                                    'qr_code_length' => strlen($qrCode),
                                ]);
                                
                                return [
                                    'success' => true,
                                    'qr_code' => $qrCode,
                                    'expires_at' => now()->addMinutes(2),
                                    'waha_session_name' => 'default', // Indicate we're using default session
                                ];
                            }
                            
                            // Try JSON response
                            $data = $response->json();
                            $qrCode = $data['qr'] ?? $data['qrcode'] ?? $data['qrCode'] ?? null;
                            
                            if ($qrCode) {
                                $qrCode = preg_replace('/^data:image\/[^;]+;base64,/', '', $qrCode);
                                
                                Log::info('WAHA: QR code retrieved from JSON with default session (WAHA Core)', [
                                    'original_session_id' => $sessionId,
                                    'waha_session_name' => 'default',
                                    'endpoint' => $endpoint,
                                    'qr_code_length' => strlen($qrCode),
                                ]);
                                
                                return [
                                    'success' => true,
                                    'qr_code' => $qrCode,
                                    'expires_at' => isset($data['expiresAt']) 
                                        ? now()->addSeconds($data['expiresAt']) 
                                        : (isset($data['expires_at']) 
                                            ? now()->addSeconds($data['expires_at']) 
                                            : now()->addMinutes(2)),
                                    'waha_session_name' => 'default',
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('WAHA: QR endpoint exception with default session', [
                            'endpoint' => $endpoint,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }
                }
            }

            Log::error('WAHA: Failed to get QR code from all endpoints', [
                'session_id' => $sessionId,
                'last_error' => $lastError,
                'is_waha_core_error' => $isWahaCoreError,
            ]);

            return [
                'success' => false,
                'error' => $lastError ?? 'Failed to get QR code from all endpoints',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get QR code error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'base_url' => $this->baseUrl,
                'exception' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get session status.
     */
    public function getSessionStatus(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/sessions/{$sessionId}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'status' => $data['status'] ?? 'unknown',
                    'data' => $data,
                ];
            }

            $errorMessage = $response->json()['message'] ?? 'Failed to get session status';
            
            // Check if error indicates WAHA Core limitation
            if ($response->status() === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::info('WAHA Core detected in getSessionStatus: Retrying with default session', [
                    'original_session_id' => $sessionId,
                ]);
                
                // Retry with 'default' session name
                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/sessions/default");
                
                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('WAHA: Session status retrieved with default session (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                        'status' => $data['status'] ?? 'unknown',
                    ]);
                    return [
                        'success' => true,
                        'status' => $data['status'] ?? 'unknown',
                        'data' => $data,
                        'waha_session_name' => 'default',
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get session status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Stop a session.
     */
    public function stopSession(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->post("{$this->baseUrl}/api/sessions/{$sessionId}/stop");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorMessage = $response->json()['message'] ?? 'Failed to stop session';
            
            // Check if error indicates WAHA Core limitation
            if ($response->status() === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::info('WAHA Core detected in stopSession: Retrying with default session', [
                    'original_session_id' => $sessionId,
                ]);
                
                // Retry with 'default' session name
                $response = $this->httpClient()
                    ->post("{$this->baseUrl}/api/sessions/default/stop");
                
                if ($response->successful()) {
                    Log::info('WAHA: Session stopped with default session (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                    ]);
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'waha_session_name' => 'default',
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA stop session error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete a session.
     */
    public function deleteSession(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->delete("{$this->baseUrl}/api/sessions/{$sessionId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            $errorMessage = $response->json()['message'] ?? 'Failed to delete session';
            
            // Check if error indicates WAHA Core limitation
            if ($response->status() === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::info('WAHA Core detected in deleteSession: Retrying with default session', [
                    'original_session_id' => $sessionId,
                ]);
                
                // Retry with 'default' session name
                $response = $this->httpClient()
                    ->delete("{$this->baseUrl}/api/sessions/default");
                
                if ($response->successful()) {
                    Log::info('WAHA: Session deleted with default session (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                    ]);
                    return [
                        'success' => true,
                        'data' => $response->json(),
                        'waha_session_name' => 'default',
                    ];
                }
            }

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA delete session error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message.
     */
    public function sendText(string $sessionId, string $chatId, string $text): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendText";
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'text' => $text,
            ];
            
            Log::info('WAHA: Sending text message', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'text_length' => strlen($text),
            ]);
            
            $response = $this->httpClient()->post($url, $payload);

            Log::info('WAHA: sendText response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendText success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Failed to send message';
            $statusCode = $response->status();
            
            // Check if error indicates WAHA Core limitation
            if ($statusCode === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::info('WAHA Core detected in sendText: Retrying with default session', [
                    'original_session_id' => $sessionId,
                ]);
                
                // Retry with 'default' session name
                $retryPayload = [
                    'session' => 'default',
                    'chatId' => $chatId,
                    'text' => $text,
                ];
                
                $retryResponse = $this->httpClient()->post($url, $retryPayload);
                
                Log::info('WAHA: sendText retry response', [
                    'status' => $retryResponse->status(),
                    'successful' => $retryResponse->successful(),
                    'body' => $retryResponse->body(),
                ]);
                
                if ($retryResponse->successful()) {
                    $responseData = $retryResponse->json();
                    Log::info('WAHA: sendText success with default session (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                        'response_data' => $responseData,
                    ]);
                    return [
                        'success' => true,
                        'data' => $responseData,
                        'waha_session_name' => 'default',
                    ];
                }
                
                // If retry also failed, update error data and message
                $errorData = $retryResponse->json();
                $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Failed to send message even with default session';
                $statusCode = $retryResponse->status();
            }
            
            Log::error('WAHA: sendText failed', [
                'status' => $statusCode,
                'error' => $errorMessage,
                'response' => $errorData,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send text error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send an image message.
     */
    public function sendImage(string $sessionId, string $chatId, string $imagePath, ?string $caption = null): array
    {
        try {
            // WAHA Plus might use sendFile for images too, or sendImage might have different format
            // Try sendFile first (same as sendDocument which works)
            $url = "{$this->baseUrl}/api/sendFile";
            
            if (!file_exists($imagePath)) {
                Log::error('WAHA: Image file not found', [
                    'image_path' => $imagePath,
                ]);
                return [
                    'success' => false,
                    'error' => 'Image file not found',
                ];
            }

            $fileContent = file_get_contents($imagePath);
            $fileName = basename($imagePath);
            $fileSize = strlen($fileContent);

            Log::info('WAHA: Sending image file', [
                'url' => $url,
                    'session' => $sessionId,
                    'chatId' => $chatId,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'has_caption' => !empty($caption),
            ]);

            // Use EXACT same format as sendDocument which works
            // Laravel HTTP client attach() automatically handles multipart
            $formData = [
                'session' => $sessionId,
                'chatId' => $chatId,
            ];
            
            if ($caption) {
                $formData['caption'] = $caption;
            }
            
            Log::info('WAHA: sendImage request details', [
                'url' => $url,
                'form_data' => $formData,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ]);
            
            $response = $this->httpClient()
                ->attach('file', $fileContent, $fileName)
                ->post($url, $formData);

            Log::info('WAHA: sendImage response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendImage success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send image';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            Log::error('WAHA: sendImage failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Handle Guzzle server exceptions and extract full error response
            $response = $e->getResponse();
            $responseBody = $response ? $response->getBody()->getContents() : '';
            $errorData = json_decode($responseBody, true) ?? [];
            
            $errorMessage = 'Failed to send image';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            Log::error('WAHA send image error (ServerException)', [
                'status' => $response ? $response->getStatusCode() : null,
                'error_message' => $errorMessage,
                'response_body' => $responseBody,
                'error_data' => $errorData,
                'image_path' => $imagePath,
            ]);
            
            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send image error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'image_path' => $imagePath,
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send an image by URL.
     * According to WAHA documentation, format should be JSON with file object containing url, mimetype, filename
     */
    public function sendImageByUrl(string $sessionId, string $chatId, string $imageUrl, ?string $caption = null): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendImage";
            
            // Get file extension and determine mimetype
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $filename = basename(parse_url($imageUrl, PHP_URL_PATH)) ?: 'image.' . ($extension ?: 'jpg');
            
            // Determine mimetype from extension
            $mimetype = 'image/jpeg'; // default
            $extensionLower = strtolower($extension);
            if (in_array($extensionLower, ['png'])) {
                $mimetype = 'image/png';
            } elseif (in_array($extensionLower, ['gif'])) {
                $mimetype = 'image/gif';
            } elseif (in_array($extensionLower, ['webp'])) {
                $mimetype = 'image/webp';
            }
            
            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'file' => [
                    'mimetype' => $mimetype,
                'url' => $imageUrl,
                    'filename' => $filename,
                ],
            ];

            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            Log::info('WAHA: Sending image by URL', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'image_url' => $imageUrl,
                'mimetype' => $mimetype,
                'filename' => $filename,
                'has_caption' => !empty($caption),
            ]);

            $response = $this->httpClient()
                ->asJson()
                ->post($url, $payload);

            Log::info('WAHA: sendImage response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendImage success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send image';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            Log::error('WAHA: sendImage failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send image by URL error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'image_url' => $imageUrl,
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a video by URL.
     * According to WAHA documentation, format should be JSON with file object containing url, mimetype, filename
     */
    public function sendVideoByUrl(string $sessionId, string $chatId, string $videoUrl, ?string $caption = null, bool $asNote = false, bool $convert = false): array
    {
        try {
            // Validate URL format
            if (!filter_var($videoUrl, FILTER_VALIDATE_URL)) {
                Log::error('WAHA: Invalid video URL format', [
                    'video_url' => $videoUrl,
                ]);
                return [
                    'success' => false,
                    'error' => "Invalid video URL format: {$videoUrl}",
                ];
            }
            
            $url = "{$this->baseUrl}/api/sendVideo";
            
            // Get file extension and determine mimetype
            $extension = pathinfo(parse_url($videoUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $filename = basename(parse_url($videoUrl, PHP_URL_PATH)) ?: 'video.' . ($extension ?: 'mp4');
            
            // Determine mimetype from extension
            $mimetype = 'video/mp4'; // default
            $extensionLower = strtolower($extension);
            if (in_array($extensionLower, ['webm'])) {
                $mimetype = 'video/webm';
            } elseif (in_array($extensionLower, ['ogg', 'ogv'])) {
                $mimetype = 'video/ogg';
            } elseif (in_array($extensionLower, ['mov'])) {
                $mimetype = 'video/quicktime';
            } elseif (in_array($extensionLower, ['avi'])) {
                $mimetype = 'video/x-msvideo';
            }
            
            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'file' => [
                    'mimetype' => $mimetype,
                    'url' => $videoUrl,
                    'filename' => $filename,
                ],
                'asNote' => $asNote,
                'convert' => $convert,
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            Log::info('WAHA: Sending video by URL', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'video_url' => $videoUrl,
                'mimetype' => $mimetype,
                'filename' => $filename,
                'has_caption' => !empty($caption),
                'as_note' => $asNote,
                'convert' => $convert,
            ]);

            // Increase timeout for video uploads (120 seconds)
            // Also increase PHP max execution time for this request
            $originalMaxExecutionTime = ini_get('max_execution_time');
            set_time_limit(120);
            
            $response = $this->httpClient(120)
                ->asJson()
                ->post($url, $payload);
            
            // Restore original max execution time
            if ($originalMaxExecutionTime !== false) {
                set_time_limit((int)$originalMaxExecutionTime);
            }

            Log::info('WAHA: sendVideo response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendVideo success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send video';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            // Check if error is related to URL access issues
            if (stripos($errorMessage, '403') !== false || stripos($errorMessage, 'forbidden') !== false) {
                $errorMessage = "Video URL is not accessible (403 Forbidden). The server hosting the video is blocking access. Please ensure the video URL is publicly accessible and not protected by authentication or IP restrictions. URL: {$videoUrl}";
            } elseif (stripos($errorMessage, '404') !== false || stripos($errorMessage, 'not found') !== false) {
                $errorMessage = "Video URL not accessible or not found. Please ensure the URL is valid and publicly accessible: {$videoUrl}";
            } elseif (stripos($errorMessage, '403') !== false) {
                $errorMessage = "Video URL access denied (403). The video server is blocking access. Please use a publicly accessible video URL without authentication or IP restrictions.";
            }
            
            Log::error('WAHA: sendVideo failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
                'video_url' => $videoUrl,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send video by URL error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'video_url' => $videoUrl,
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a document.
     */
    public function sendDocument(string $sessionId, string $chatId, string $documentPath, ?string $filename = null): array
    {
        try {
            $response = $this->httpClient()
                ->attach('file', file_get_contents($documentPath), $filename ?? basename($documentPath))
                ->post("{$this->baseUrl}/api/sendFile", [
                    'session' => $sessionId,
                    'chatId' => $chatId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to send document',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send document error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a document by URL (without uploading from local filesystem).
     * According to WAHA documentation, format should be JSON with file object containing url, mimetype, filename
     */
    public function sendDocumentByUrl(string $sessionId, string $chatId, string $documentUrl, ?string $filename = null, ?string $caption = null): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendFile";
            
            // Get file extension and determine mimetype
            $extension = pathinfo(parse_url($documentUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $fileBasename = basename(parse_url($documentUrl, PHP_URL_PATH)) ?: 'document.' . ($extension ?: 'pdf');
            $finalFilename = $filename ?? $fileBasename;
            
            // Determine mimetype from extension
            $mimetype = 'application/pdf'; // default
            $extensionLower = strtolower($extension);
            
            // Common document mimetypes
            if (in_array($extensionLower, ['pdf'])) {
                $mimetype = 'application/pdf';
            } elseif (in_array($extensionLower, ['doc'])) {
                $mimetype = 'application/msword';
            } elseif (in_array($extensionLower, ['docx'])) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            } elseif (in_array($extensionLower, ['xls'])) {
                $mimetype = 'application/vnd.ms-excel';
            } elseif (in_array($extensionLower, ['xlsx'])) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            } elseif (in_array($extensionLower, ['ppt'])) {
                $mimetype = 'application/vnd.ms-powerpoint';
            } elseif (in_array($extensionLower, ['pptx'])) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            } elseif (in_array($extensionLower, ['zip'])) {
                $mimetype = 'application/zip';
            } elseif (in_array($extensionLower, ['txt'])) {
                $mimetype = 'text/plain';
            } elseif (in_array($extensionLower, ['csv'])) {
                $mimetype = 'text/csv';
            } elseif (in_array($extensionLower, ['json'])) {
                $mimetype = 'application/json';
            }
            
            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'file' => [
                    'mimetype' => $mimetype,
                    'url' => $documentUrl,
                    'filename' => $finalFilename,
                ],
            ];
            
            if ($caption) {
                $payload['caption'] = $caption;
            }
            
            Log::info('WAHA: Sending document by URL', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'document_url' => $documentUrl,
                'mimetype' => $mimetype,
                'filename' => $finalFilename,
                'has_caption' => !empty($caption),
            ]);

            $response = $this->httpClient()
                ->asJson()
                ->post($url, $payload);

            Log::info('WAHA: sendFile response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendFile success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send document';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            Log::error('WAHA: sendFile failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send document by URL error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'document_url' => $documentUrl,
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a poll message.
     * According to WAHA documentation, format should be JSON with poll object
     * Note: WEBJS engine does not support polls, so we'll try the API first
     * and if it fails with "not implemented", we can optionally send as formatted text
     */
    public function sendPoll(string $sessionId, string $chatId, string $pollName, array $options, bool $multipleAnswers = false, bool $fallbackToText = false): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendPoll";
            
            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'poll' => [
                    'name' => $pollName,
                    'options' => $options,
                    'multipleAnswers' => $multipleAnswers,
                ],
            ];
            
            Log::info('WAHA: Sending poll', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'poll_name' => $pollName,
                'options_count' => count($options),
                'multiple_answers' => $multipleAnswers,
                'fallback_to_text' => $fallbackToText,
            ]);

            $response = $this->httpClient()
                ->asJson()
                ->post($url, $payload);

            Log::info('WAHA: sendPoll response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendPoll success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send poll';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            // Check if error indicates feature not supported by engine
            $isNotSupported = stripos($errorMessage, 'not implemented') !== false || 
                            stripos($errorMessage, 'not supported') !== false ||
                            stripos($errorMessage, 'WEBJS') !== false;
            
            // If fallback is enabled and feature is not supported, send as formatted text
            if ($fallbackToText && $isNotSupported) {
                Log::info('WAHA: Poll not supported, falling back to text message', [
                    'session' => $sessionId,
                    'chatId' => $chatId,
                ]);
                
                // Format poll as text message
                $textMessage = "📊 *{$pollName}*\n\n";
                foreach ($options as $index => $option) {
                    $emoji = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟', '1️⃣1️⃣', '1️⃣2️⃣'];
                    $textMessage .= ($emoji[$index] ?? ($index + 1) . '.') . " {$option}\n";
                }
                if ($multipleAnswers) {
                    $textMessage .= "\n*Note: Multiple answers allowed*";
                } else {
                    $textMessage .= "\n*Note: Single answer only*";
                }
                $textMessage .= "\n\n_Poll feature not supported by your WAHA engine. This is a text representation._";
                
                // Send as text message
                $textResult = $this->sendText($sessionId, $chatId, $textMessage);
                
                // Return with flag indicating fallback was used
                if ($textResult['success']) {
                    return [
                        'success' => true,
                        'data' => $textResult['data'],
                        'fallback_used' => true,
                        'original_type' => 'poll',
                    ];
                }
                
                // If text also failed, return the original poll error
                return [
                    'success' => false,
                    'error' => 'Poll not supported and fallback to text also failed: ' . ($textResult['error'] ?? 'Unknown error'),
                    'engine_not_supported' => true,
                ];
            }
            
            Log::error('WAHA: sendPoll failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
                'is_not_supported' => $isNotSupported,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'engine_not_supported' => $isNotSupported,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send poll error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a button message.
     * According to WAHA documentation, format should be JSON with buttons array
     * 
     * WARNING: Send Buttons is DEPRECATED in WAHA and may not work as expected.
     * Buttons are fragile and may not be delivered. Consider using Send Text or Polls as fallback.
     * 
     * @param bool $fallbackToText If true, will send as formatted text message if button fails or status is PENDING
     */
    public function sendButton(string $sessionId, string $chatId, string $body, array $buttons, ?string $header = null, ?string $footer = null, ?array $headerImage = null, bool $fallbackToText = false): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendButtons";
            
            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'body' => $body,
                'buttons' => $buttons,
            ];
            
            if ($header) {
                $payload['header'] = $header;
            }
            
            if ($footer) {
                $payload['footer'] = $footer;
            }
            
            if ($headerImage) {
                $payload['headerImage'] = $headerImage;
            }
            
            Log::info('WAHA: Sending button message', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'body_length' => strlen($body),
                'buttons_count' => count($buttons),
                'has_header' => !empty($header),
                'has_footer' => !empty($footer),
                'has_header_image' => !empty($headerImage),
            ]);

            $response = $this->httpClient()
                ->asJson()
                ->post($url, $payload);

            Log::info('WAHA: sendButton response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Check if status is PENDING - buttons are deprecated and may not work
                $statusFromResponse = $responseData['status'] ?? null;
                $isPending = $statusFromResponse === 'PENDING';
                
                Log::info('WAHA: sendButton success', [
                    'response_data' => $responseData,
                    'status' => $statusFromResponse,
                    'is_pending' => $isPending,
                ]);
                
                // If fallback is enabled and status is PENDING, send as text message
                if ($fallbackToText && $isPending) {
                    Log::warning('WAHA: Button message status is PENDING, falling back to text message', [
                        'session' => $sessionId,
                        'chatId' => $chatId,
                        'note' => 'Buttons are deprecated in WAHA and may not work as expected',
                    ]);
                    
                    // Format button message as text
                    $textMessage = '';
                    if ($header) {
                        $textMessage .= "*{$header}*\n\n";
                    }
                    $textMessage .= "{$body}\n\n";
                    
                    // Add buttons as numbered options
                    foreach ($buttons as $index => $button) {
                        $emoji = ['1️⃣', '2️⃣', '3️⃣'];
                        $textMessage .= ($emoji[$index] ?? ($index + 1) . '.') . " {$button['text']}";
                        if ($button['type'] === 'url' && isset($button['url'])) {
                            $textMessage .= " - {$button['url']}";
                        } elseif ($button['type'] === 'call' && isset($button['phoneNumber'])) {
                            $textMessage .= " - {$button['phoneNumber']}";
                        } elseif ($button['type'] === 'copy' && isset($button['copyCode'])) {
                            $textMessage .= " - Code: {$button['copyCode']}";
                        }
                        $textMessage .= "\n";
                    }
                    
                    if ($footer) {
                        $textMessage .= "\n_{$footer}_";
                    }
                    
                    $textMessage .= "\n\n⚠️ _Button messages are deprecated in WAHA. This is a text representation._";
                    
                    // Send as text message
                    $textResult = $this->sendText($sessionId, $chatId, $textMessage);
                    
                    // Return with flag indicating fallback was used
                    if ($textResult['success']) {
                        return [
                            'success' => true,
                            'data' => $textResult['data'],
                            'fallback_used' => true,
                            'original_type' => 'button',
                            'original_response' => $responseData,
                        ];
                    }
                }
                
                return [
                    'success' => true,
                    'data' => $responseData,
                    'status' => $statusFromResponse,
                    'is_pending' => $isPending,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send button message';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }
            
            Log::error('WAHA: sendButton failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send button error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build webhook URL for a session (BUILT-IN webhook)
     * Handles Docker networking automatically
     */
    protected function buildWebhookUrl(string $sessionId): string
    {
        $appUrl = config('app.url', 'http://localhost:8000');
        
        // Check if WAHA is running in Docker
        $wahaUrl = config('services.waha.url', 'http://localhost:3000');
        $isWahaInDocker = str_contains($wahaUrl, 'localhost') && 
                           (str_contains($wahaUrl, ':3000') || 
                            str_contains($wahaUrl, ':3001') || 
                            str_contains($wahaUrl, ':3002'));
        
        // If WAHA is in Docker and app URL uses localhost, replace with host.docker.internal
        if ($isWahaInDocker && str_contains($appUrl, 'localhost')) {
            $os = strtoupper(PHP_OS);
            if (str_contains($os, 'DARWIN') || str_contains($os, 'WIN')) {
                $appUrl = str_replace('localhost', 'host.docker.internal', $appUrl);
            } else {
                $hostIp = env('DOCKER_HOST_IP', 'host.docker.internal');
                $appUrl = str_replace('localhost', $hostIp, $appUrl);
            }
        } else {
            // Fix: Always add port 8000 for localhost in development
            if (str_contains($appUrl, 'localhost')) {
                if (!preg_match('/localhost:\d+/', $appUrl)) {
                    $appUrl = 'http://localhost:8000';
                } elseif (!str_contains($appUrl, ':8000') && str_contains($appUrl, 'localhost')) {
                    $appUrl = preg_replace('/localhost:\d+/', 'localhost:8000', $appUrl);
                }
            }
        }
        
        return rtrim($appUrl, '/') . '/webhook/receive/' . $sessionId;
    }

    /**
     * Get webhook URL for a session (BUILT-IN webhook)
     */
    public function getWebhookUrl(string $sessionId): string
    {
        return $this->buildWebhookUrl($sessionId);
    }

    /**
     * Update webhook configuration for existing session
     * According to WAHA docs: https://waha.devlike.pro/docs/how-to/receive-messages/
     * WAHA doesn't have a direct update webhook endpoint, so we need to restart session with new config
     */
    public function updateWebhook(string $sessionId): array
    {
        try {
            $webhookUrl = $this->getWebhookUrl($sessionId);
            
            Log::info('WAHA: Updating webhook configuration', [
                'session_id' => $sessionId,
                'webhook_url' => $webhookUrl,
                'app_url' => config('app.url'),
            ]);
            
            // Check if WAHA Core (uses 'default' session)
            $wahaSessionName = 'default'; // WAHA Core always uses 'default'
            
            // Get current session config
            $getUrl = "{$this->baseUrl}/api/sessions/{$wahaSessionName}";
            $getResponse = $this->httpClient()->get($getUrl);
            
            if (!$getResponse->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to get session config: ' . $getResponse->body(),
                ];
            }
            
            $sessionData = $getResponse->json();
            $currentConfig = $sessionData['config'] ?? [];
            
            // Update webhook config
            $engine = $currentConfig['engine'] ?? env('WAHA_DEFAULT_ENGINE', 'GOWS');
            $currentConfig['engine'] = $engine;
            $currentConfig['webhooks'] = [
                [
                    'url' => $webhookUrl,
                    'events' => [
                        'message',
                        'message.any',
                        'message.ack',
                        'message.reaction',
                        'message.edited',
                        'message.revoked',
                        'session.status',
                    ],
                ],
            ];
            
            // Stop session first
            $stopUrl = "{$this->baseUrl}/api/sessions/{$wahaSessionName}/stop";
            $stopResponse = $this->httpClient()->post($stopUrl);
            
            if (!$stopResponse->successful()) {
                Log::warning('WAHA: Failed to stop session before webhook update', [
                    'status' => $stopResponse->status(),
                    'body' => $stopResponse->body(),
                ]);
            }
            
            // Wait a bit for session to stop
            sleep(1);
            
            // Start session with updated config
            $startUrl = "{$this->baseUrl}/api/sessions/start";
            $payload = [
                'name' => $wahaSessionName,
                'config' => $currentConfig,
            ];
            
            $startResponse = $this->httpClient()->post($startUrl, $payload);
            
            if ($startResponse->successful()) {
                Log::info('WAHA: Webhook updated successfully', [
                    'session_id' => $sessionId,
                    'waha_session_name' => $wahaSessionName,
                    'webhook_url' => $webhookUrl,
                ]);
                
                return [
                    'success' => true,
                    'webhook_url' => $webhookUrl,
                    'message' => 'Webhook berhasil diupdate. Session telah di-restart dengan konfigurasi baru.',
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to restart session: ' . $startResponse->body(),
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('WAHA update webhook error: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'exception' => $e,
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get contacts for a session.
     * @deprecated Use getAllContacts instead
     */
    public function getContacts(string $sessionId): array
    {
        return $this->getAllContacts($sessionId);
    }

    /**
     * Get all contacts for a session with pagination.
     */
    public function getAllContacts(string $sessionId, int $limit = 100, int $offset = 0, string $sortBy = 'id', string $sortOrder = 'asc'): array
    {
        try {
            $params = [
                'session' => $sessionId,
                'limit' => $limit,
                'offset' => $offset,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ];

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/contacts/all", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get contacts',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get all contacts error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get a single contact by ID.
     */
    public function getContact(string $sessionId, string $contactId): array
    {
        try {
            $params = [
                'contactId' => $contactId,
                'session' => $sessionId,
            ];

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/contacts", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get contact',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get contact error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update contact information.
     */
    public function updateContact(string $sessionId, string $chatId, string $firstName, ?string $lastName = null): array
    {
        try {
            $payload = [
                'firstName' => $firstName,
            ];

            if ($lastName !== null) {
                $payload['lastName'] = $lastName;
            }

            $response = $this->httpClient()
                ->put("{$this->baseUrl}/api/{$sessionId}/contacts/{$chatId}", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to update contact',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA update contact error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if phone number exists in WhatsApp.
     */
    public function checkPhoneExists(string $sessionId, string $phone): array
    {
        try {
            $params = [
                'phone' => $phone,
                'session' => $sessionId,
            ];

            Log::info('WAHA: Checking phone exists', [
                'session' => $sessionId,
                'phone' => $phone,
                'url' => "{$this->baseUrl}/api/contacts/check-exists",
            ]);

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/contacts/check-exists", $params);

            Log::info('WAHA: check-exists response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: check-exists success', [
                    'response_data' => $responseData,
                ]);
                
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            // Handle different error formats
            $errorData = null;
            $errorMessage = 'Failed to check phone number';
            
            try {
                $errorData = $response->json();
            } catch (\Exception $jsonException) {
                Log::warning('WAHA: Failed to parse error response as JSON', [
                    'body' => $response->body(),
                    'json_error' => $jsonException->getMessage(),
                ]);
                $errorMessage = 'Invalid response from WAHA: ' . $response->body();
            }
            
            if ($errorData) {
                // Try to extract error message from various possible formats
                if (isset($errorData['message'])) {
                    $errorMessage = is_array($errorData['message']) 
                        ? implode(', ', $errorData['message']) 
                        : $errorData['message'];
                } elseif (isset($errorData['error'])) {
                    $errorMessage = is_array($errorData['error']) 
                        ? implode(', ', $errorData['error']) 
                        : $errorData['error'];
                } elseif (isset($errorData['exception']['message'])) {
                    $errorMessage = $errorData['exception']['message'];
                } elseif (isset($errorData['exception']['error'])) {
                    $errorMessage = $errorData['exception']['error'];
                }
            }

            Log::error('WAHA: check-exists failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'error_data' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA check phone exists error: ' . $e->getMessage(), [
                'session' => $sessionId,
                'phone' => $phone,
                'exception' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get contact "about" information.
     */
    public function getContactAbout(string $sessionId, string $contactId): array
    {
        try {
            $params = [
                'contactId' => $contactId,
                'session' => $sessionId,
            ];

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/contacts/about", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get contact about',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get contact about error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get contact profile picture.
     */
    public function getContactProfilePicture(string $sessionId, string $contactId, bool $refresh = false): array
    {
        try {
            $params = [
                'contactId' => $contactId,
                'session' => $sessionId,
            ];

            if ($refresh) {
                $params['refresh'] = 'True';
            }

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/contacts/profile-picture", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get profile picture',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get profile picture error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Block a contact.
     */
    public function blockContact(string $sessionId, string $contactId): array
    {
        try {
            $payload = [
                'contactId' => $contactId,
                'session' => $sessionId,
            ];

            $response = $this->httpClient()
                ->post("{$this->baseUrl}/api/contacts/block", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to block contact',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA block contact error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Unblock a contact.
     */
    public function unblockContact(string $sessionId, string $contactId): array
    {
        try {
            $payload = [
                'contactId' => $contactId,
                'session' => $sessionId,
            ];

            $response = $this->httpClient()
                ->post("{$this->baseUrl}/api/contacts/unblock", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to unblock contact',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA unblock contact error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all known LIDs (Linked IDs) for a session.
     */
    public function getAllLids(string $sessionId, int $limit = 100, int $offset = 0): array
    {
        try {
            $params = [
                'limit' => $limit,
                'offset' => $offset,
            ];

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/lids", $params);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get LIDs',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get all LIDs error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get count of LIDs for a session.
     */
    public function getLidsCount(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/lids/count");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get LIDs count',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get LIDs count error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get phone number by LID.
     */
    public function getPhoneByLid(string $sessionId, string $lid): array
    {
        try {
            // Escape @ in lid
            $lidEscaped = str_replace('@', '%40', $lid);

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/lids/{$lidEscaped}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get phone by LID',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get phone by LID error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get LID by phone number.
     */
    public function getLidByPhone(string $sessionId, string $phoneNumber): array
    {
        try {
            // Escape @ in phoneNumber
            $phoneEscaped = str_replace('@', '%40', $phoneNumber);

            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/lids/pn/{$phoneEscaped}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get LID by phone',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get LID by phone error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get groups for a session.
     */
    public function getGroups(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/groups");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to get groups',
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get groups error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get all chats for a session.
     */
    public function getChats(string $sessionId): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api/{$sessionId}/chats");

            Log::info('WAHA: getChats response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $chats = $response->json();
                Log::info('WAHA: getChats success', [
                    'chats_count' => is_array($chats) ? count($chats) : 0,
                ]);

                return [
                    'success' => true,
                    'data' => $chats,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? 'Failed to get chats';
            
            // Check if error indicates WAHA Core limitation
            if ($response->status() === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::info('WAHA Core detected in getChats: Retrying with default session', [
                    'original_session_id' => $sessionId,
                ]);
                
                // Retry with 'default' session name
                $response = $this->httpClient()
                    ->get("{$this->baseUrl}/api/default/chats");
                
                if ($response->successful()) {
                    $chats = $response->json();
                    Log::info('WAHA: getChats success with default session (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                        'chats_count' => is_array($chats) ? count($chats) : 0,
                    ]);
                    return [
                        'success' => true,
                        'data' => $chats,
                        'waha_session_name' => 'default',
                    ];
                }
            }
            
            Log::error('WAHA: getChats failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get chats error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a list message (interactive list).
     */
    public function sendList(string $sessionId, string $chatId, array $message, ?string $replyTo = null): array
    {
        try {
            $url = "{$this->baseUrl}/api/sendList";

            // Build payload according to WAHA documentation
            $payload = [
                'session' => $sessionId,
                'chatId' => $chatId,
                'message' => $message,
            ];

            if ($replyTo) {
                $payload['reply_to'] = $replyTo;
            }

            Log::info('WAHA: Sending list message', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'message_title' => $message['title'] ?? null,
                'sections_count' => count($message['sections'] ?? []),
                'has_reply_to' => !empty($replyTo),
            ]);

            $response = $this->httpClient()
                ->asJson()
                ->post($url, $payload);

            Log::info('WAHA: sendList response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: sendList success', [
                    'response_data' => $responseData,
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to send list message';
            if (isset($errorData['exception']['message'])) {
                $errorMessage = $errorData['exception']['message'];
            } elseif (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }

            Log::error('WAHA: sendList failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'response' => $errorData,
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA send list error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get messages from WAHA API
     * 
     * @param string $sessionId
     * @param string|null $chatId Optional chat ID to filter messages
     * @param int $limit Maximum number of messages to retrieve
     * @return array
     */
    public function getMessages(string $sessionId, ?string $chatId = null, int $limit = 100): array
    {
        try {
            $url = "{$this->baseUrl}/api/messages";
            
            $params = [
                'session' => $sessionId,
                'limit' => $limit,
            ];

            // Only add chatId if provided (some WAHA versions allow getting all messages without chatId)
            if ($chatId) {
                $params['chatId'] = $chatId;
            }

            Log::info('WAHA: Getting messages', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'limit' => $limit,
            ]);

            $response = $this->httpClient()
                ->get($url, $params);

            Log::info('WAHA: getMessages response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: getMessages success', [
                    'messages_count' => is_array($responseData) ? count($responseData) : 0,
                ]);

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to get messages';
            
            // Handle different error formats
            if (isset($errorData['message'])) {
                $errorMessage = is_array($errorData['message']) 
                    ? implode(', ', $errorData['message']) 
                    : $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = is_array($errorData['error']) 
                    ? implode(', ', $errorData['error']) 
                    : $errorData['error'];
            } elseif (is_array($errorData)) {
                // If errorData itself is an array of error messages
                $errorMessage = implode(', ', $errorData);
            }

            // Check if error is about WAHA Core only supporting 'default' session
            if ($response->status() === 422 && 
                (stripos($errorMessage, 'WAHA Core support only') !== false || 
                 stripos($errorMessage, 'default session') !== false) &&
                $sessionId !== 'default') {
                
                Log::warning('WAHA Core detected in getMessages: Retrying with default session name', [
                    'original_session_id' => $sessionId,
                    'retry_with' => 'default',
                    'error' => $errorMessage,
                ]);
                
                // Retry with 'default' session name for WAHA Core
                $params['session'] = 'default';
                
                Log::info('WAHA: Retrying getMessages with default session name', [
                    'original_session_id' => $sessionId,
                    'waha_session_name' => 'default',
                    'chatId' => $chatId,
                    'limit' => $limit,
                ]);
                
                // Retry the request with 'default' session name
                $response = $this->httpClient()
                    ->get($url, $params);
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    Log::info('WAHA: getMessages success with default session name (WAHA Core)', [
                        'original_session_id' => $sessionId,
                        'waha_session_name' => 'default',
                        'messages_count' => is_array($responseData) ? count($responseData) : 0,
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $responseData,
                    ];
                }
                
                // If retry with default also failed, continue to return error below
                $errorData = $response->json();
                if (isset($errorData['message'])) {
                    $errorMessage = is_array($errorData['message']) 
                        ? implode(', ', $errorData['message']) 
                        : $errorData['message'];
                } elseif (isset($errorData['error'])) {
                    $errorMessage = is_array($errorData['error']) 
                        ? implode(', ', $errorData['error']) 
                        : $errorData['error'];
                }
            }

            Log::error('WAHA: getMessages failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'error_data' => $errorData,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get messages error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get message by ID from WAHA API
     * 
     * @param string $sessionId
     * @param string $chatId
     * @param string $messageId
     * @param bool $downloadMedia Whether to download media
     * @return array
     */
    public function getMessageById(string $sessionId, string $chatId, string $messageId, bool $downloadMedia = false): array
    {
        try {
            $url = "{$this->baseUrl}/api/{$sessionId}/chats/{$chatId}/messages/{$messageId}";
            
            $params = [];
            if ($downloadMedia) {
                $params['downloadMedia'] = 'true';
            }

            Log::info('WAHA: Getting message by ID', [
                'url' => $url,
                'session' => $sessionId,
                'chatId' => $chatId,
                'messageId' => $messageId,
                'downloadMedia' => $downloadMedia,
            ]);

            $response = $this->httpClient()
                ->get($url, $params);

            Log::info('WAHA: getMessageById response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('WAHA: getMessageById success');

                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = 'Failed to get message';
            if (isset($errorData['message'])) {
                $errorMessage = $errorData['message'];
            } elseif (isset($errorData['error'])) {
                $errorMessage = $errorData['error'];
            }

            Log::error('WAHA: getMessageById failed', [
                'status' => $response->status(),
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        } catch (\Exception $e) {
            Log::error('WAHA get message by ID error: ' . $e->getMessage(), [
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

