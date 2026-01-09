<?php

namespace App\Console\Commands;

use App\Models\WhatsAppSession;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:test {session_id?} {--from-docker : Test from Docker container}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook endpoint untuk memastikan WAHA dapat mengirim webhook';

    protected WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::debug('WebhookTest: Starting webhook test command', [
            'arguments' => $this->arguments(),
            'options' => $this->options(),
        ]);

        $sessionId = $this->argument('session_id');
        
        // If no session ID provided, try to get from database
        if (!$sessionId) {
            Log::debug('WebhookTest: No session_id provided, searching for latest session');
            $session = WhatsAppSession::latest()->first();
            if (!$session) {
                Log::warning('WebhookTest: No session found in database');
                $this->error('No session found. Please provide session_id or create a session first.');
                return 1;
            }
            $sessionId = $session->session_id;
            Log::info('WebhookTest: Using latest session from database', ['session_id' => $sessionId]);
            $this->info("Using latest session: {$sessionId}");
        } else {
            Log::debug('WebhookTest: Using provided session_id', ['session_id' => $sessionId]);
            $session = WhatsAppSession::where('session_id', $sessionId)->first();
            if (!$session) {
                Log::warning('WebhookTest: Session not found in database', ['session_id' => $sessionId]);
                $this->warn("Session {$sessionId} not found in database, but continuing test...");
            } else {
                Log::debug('WebhookTest: Session found in database', [
                    'session_id' => $sessionId,
                    'status' => $session->status,
                ]);
            }
        }

        $webhookUrl = $this->wahaService->getWebhookUrl($sessionId);
        
        Log::info('WebhookTest: Starting webhook test', [
            'session_id' => $sessionId,
            'webhook_url' => $webhookUrl,
            'app_url' => config('app.url'),
            'waha_url' => config('services.waha.url'),
        ]);
        
        $this->info("==========================================");
        $this->info("Testing WAHA Webhook");
        $this->info("==========================================");
        $this->info("Session ID: {$sessionId}");
        $this->info("Webhook URL: {$webhookUrl}");
        $this->newLine();

        // Test 1: Check if webhook endpoint is accessible
        $this->info("Test 1: Checking webhook endpoint accessibility...");
        Log::debug('WebhookTest: Test 1 - Checking endpoint accessibility', [
            'webhook_url' => $webhookUrl,
        ]);
        
        try {
            $testPayload = [
                'event' => 'test',
                'payload' => [],
            ];
            
            Log::debug('WebhookTest: Sending test request', [
                'url' => $webhookUrl,
                'payload' => $testPayload,
            ]);
            
            $response = Http::timeout(5)->post($webhookUrl, $testPayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            
            Log::debug('WebhookTest: Test 1 response received', [
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'headers' => $response->headers(),
            ]);
            
            if ($statusCode == 200 || $statusCode == 404) {
                Log::info('WebhookTest: Test 1 passed - Endpoint is accessible', [
                    'status_code' => $statusCode,
                ]);
                $this->info("✓ Webhook endpoint is accessible (HTTP {$statusCode})");
                if ($statusCode == 404) {
                    Log::warning('WebhookTest: Test 1 - 404 response (session might not exist)', [
                        'session_id' => $sessionId,
                    ]);
                    $this->warn("  Note: 404 might be OK if session doesn't exist, but endpoint should be reachable");
                }
            } else {
                Log::error('WebhookTest: Test 1 failed - Unexpected status code', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                ]);
                $this->error("✗ Webhook endpoint returned HTTP {$statusCode}");
                $this->error("  Response: " . $responseBody);
            }
        } catch (\Exception $e) {
            Log::error('WebhookTest: Test 1 exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("✗ Failed to reach webhook endpoint: " . $e->getMessage());
        }
        $this->newLine();

        // Test 2: Test from Docker container (if requested)
        if ($this->option('from-docker')) {
            $this->info("Test 2: Testing webhook from Docker container...");
            $this->warn("  This requires docker exec access. Skipping for now.");
            $this->warn("  Use: docker exec waha-api curl -X POST '{$webhookUrl}' -H 'Content-Type: application/json' -d '{\"event\":\"test\",\"payload\":{}}'");
            $this->newLine();
        }

        // Test 3: Send test message event
        $this->info("Test 3: Sending test message event...");
        Log::debug('WebhookTest: Test 3 - Sending test message event', [
            'webhook_url' => $webhookUrl,
        ]);
        
        try {
            $testPayload = [
                'event' => 'message',
                'payload' => [
                    'from' => '6281234567890@c.us',
                    'to' => '6289876543210@c.us',
                    'body' => 'Test message from webhook test command',
                    'timestamp' => time(),
                    'id' => [
                        'fromMe' => false,
                        'remote' => '6281234567890@c.us',
                        'id' => 'test_' . time(),
                    ],
                ],
            ];

            Log::debug('WebhookTest: Sending test message payload', [
                'url' => $webhookUrl,
                'payload' => $testPayload,
            ]);

            $response = Http::timeout(10)->post($webhookUrl, $testPayload);

            $statusCode = $response->status();
            $responseBody = $response->body();
            
            Log::debug('WebhookTest: Test 3 response received', [
                'status_code' => $statusCode,
                'successful' => $response->successful(),
                'response_body' => $responseBody,
            ]);

            if ($response->successful()) {
                Log::info('WebhookTest: Test 3 passed - Test message sent successfully', [
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]);
                $this->info("✓ Test message event sent successfully");
                $this->info("  Response: " . $responseBody);
            } else {
                Log::error('WebhookTest: Test 3 failed - Failed to send test message', [
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                ]);
                $this->error("✗ Failed to send test message event");
                $this->error("  Status: " . $statusCode);
                $this->error("  Response: " . $responseBody);
            }
        } catch (\Exception $e) {
            Log::error('WebhookTest: Test 3 exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("✗ Failed to send test message: " . $e->getMessage());
        }
        $this->newLine();

        // Test 4: Check WAHA session status
        $this->info("Test 4: Checking WAHA session status...");
        Log::debug('WebhookTest: Test 4 - Checking WAHA session status', [
            'session_id' => $sessionId,
        ]);
        
        try {
            $statusResult = $this->wahaService->getSessionStatus($sessionId);
            
            Log::debug('WebhookTest: WAHA session status result', [
                'success' => $statusResult['success'] ?? false,
                'status' => $statusResult['status'] ?? null,
                'data' => $statusResult['data'] ?? null,
                'error' => $statusResult['error'] ?? null,
            ]);
            
            if ($statusResult['success']) {
                $status = $statusResult['status'];
                Log::info('WebhookTest: Test 4 - Session status retrieved', [
                    'session_id' => $sessionId,
                    'status' => $status,
                ]);
                $this->info("  WAHA session status: {$status}");
                
                if ($status == 'WORKING') {
                    Log::info('WebhookTest: Session is WORKING - webhook should receive messages');
                    $this->info("  ✓ Session is working - webhook should receive messages");
                } else {
                    Log::warning('WebhookTest: Session not WORKING - webhook may not receive messages', [
                        'status' => $status,
                    ]);
                    $this->warn("  ⚠ Session status is {$status} - webhook may not receive messages until session is WORKING");
                }
            } else {
                $error = $statusResult['error'] ?? 'Unknown error';
                Log::error('WebhookTest: Test 4 failed - Failed to get session status', [
                    'error' => $error,
                ]);
                $this->error("  ✗ Failed to get session status: " . $error);
            }
        } catch (\Exception $e) {
            Log::error('WebhookTest: Test 4 exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("  ✗ Error checking session status: " . $e->getMessage());
        }
        $this->newLine();

        // Test 5: Show webhook configuration info
        $this->info("Test 5: Webhook Configuration Info");
        
        $configInfo = [
            'webhook_url' => $webhookUrl,
            'app_url' => config('app.url', 'http://localhost:8000'),
            'waha_url' => config('services.waha.url', 'http://localhost:3000'),
            'waha_api_key_set' => !empty(config('services.waha.api_key')),
            'docker_host_ip' => env('DOCKER_HOST_IP'),
            'is_waha_in_docker' => str_contains(config('services.waha.url', ''), 'localhost') && 
                                   (str_contains(config('services.waha.url', ''), ':3000') || 
                                    str_contains(config('services.waha.url', ''), ':3001') || 
                                    str_contains(config('services.waha.url', ''), ':3002')),
        ];
        
        Log::debug('WebhookTest: Test 5 - Configuration info', $configInfo);
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['Webhook URL', $webhookUrl],
                ['App URL', config('app.url', 'http://localhost:8000')],
                ['WAHA URL', config('services.waha.url', 'http://localhost:3000')],
                ['WAHA API Key', config('services.waha.api_key') ? 'Set (' . substr(config('services.waha.api_key'), 0, 4) . '...)' : 'Not set'],
                ['Docker Host IP', env('DOCKER_HOST_IP', 'Not set (using default)')],
                ['WAHA in Docker', $configInfo['is_waha_in_docker'] ? 'Yes' : 'No'],
            ]
        );
        $this->newLine();

        Log::info('WebhookTest: Webhook test completed', [
            'session_id' => $sessionId,
            'webhook_url' => $webhookUrl,
        ]);
        
        $this->info("==========================================");
        $this->info("Webhook Test Complete");
        $this->info("==========================================");
        $this->newLine();
        
        $this->info("Next steps:");
        $this->line("1. Ensure Laravel app is running on port 8000");
        $this->line("2. Check Laravel logs: tail -f storage/logs/laravel.log");
        $this->line("3. Check debug logs: tail -f storage/logs/laravel.log | grep WebhookTest");
        $this->line("4. Send a test message to your WhatsApp number");
        $this->line("5. Check if webhook receives the message in logs");
        $this->newLine();
        
        $this->info("Troubleshooting:");
        $this->line("- If Docker can't reach webhook, set DOCKER_HOST_IP in .env");
        $this->line("- For Linux, you may need: DOCKER_HOST_IP=<your-host-ip>");
        $this->line("- For macOS/Windows, host.docker.internal should work");
        $this->line("- Check webhook URL in WAHA session config matches the URL above");
        $this->line("- View detailed debug logs: tail -f storage/logs/laravel.log | grep -E 'WebhookTest|webhook'");
        $this->newLine();
        
        return 0;
    }
}

