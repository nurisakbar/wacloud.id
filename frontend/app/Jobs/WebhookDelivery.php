<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Queue\Middleware\ThrottlesExceptions;

class WebhookDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;
    public $backoff = [5, 15, 30];
    
    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Throttle exceptions: if webhook fails 5 times in 1 minute, delay for 1 minute
            (new ThrottlesExceptions(5, 1))->backoff(60),
        ];
    }

    protected $webhookId;
    protected $payload;
    protected $event;

    /**
     * Create a new job instance.
     */
    public function __construct(string $webhookId, string $event, array $payload)
    {
        $this->webhookId = $webhookId;
        $this->event = $event;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);
        if (!$webhook || !$webhook->is_active) {
            Log::warning('WebhookDelivery Job: Webhook not found or inactive', [
                'webhook_id' => $this->webhookId,
            ]);
            return;
        }

        // Validate webhook URL - skip if it points to internal application routes
        if ($this->isInternalUrl($webhook->url)) {
            Log::warning('WebhookDelivery Job: Skipping webhook with internal URL', [
                'webhook_id' => $this->webhookId,
                'url' => $webhook->url,
                'message' => 'Webhook URL points to internal application route. Please update webhook URL to an external endpoint.',
            ]);
            
            // Log as failed delivery
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event_type' => $this->event,
                'payload' => $this->payload,
                'response_status' => 0,
                'error_message' => 'Webhook URL points to internal application route. Use external URL instead.',
                'triggered_at' => now(),
            ]);
            
            return;
        }

        // Rate limiting per webhook URL to prevent overwhelming destination servers
        $rateLimitKey = 'webhook-url:' . md5($webhook->url);
        $maxRequestsPerMinute = 30; // Max 30 requests per minute per URL
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxRequestsPerMinute)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::warning('WebhookDelivery Job: Rate limit exceeded for webhook URL', [
                'webhook_id' => $this->webhookId,
                'url' => $webhook->url,
                'retry_after_seconds' => $seconds,
            ]);
            
            // Release job back to queue with delay
            $this->release($seconds);
            return;
        }
        
        // Increment rate limiter
        RateLimiter::hit($rateLimitKey, 60); // 60 seconds window

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WAHA-SaaS/1.0',
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $this->payload);

            $statusCode = $response->status();
            $success = $statusCode >= 200 && $statusCode < 300;

            // Log webhook delivery
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event_type' => $this->event,
                'payload' => $this->payload,
                'response_status' => $statusCode,
                'response_body' => $response->body(),
                'triggered_at' => now(),
            ]);

            if ($success) {
                $webhook->update([
                    'last_triggered_at' => now(),
                ]);
            } else {
                $webhook->increment('failure_count');
                Log::warning('WebhookDelivery Job: Webhook delivery failed', [
                    'webhook_id' => $this->webhookId,
                    'status_code' => $statusCode,
                    'response' => $response->body(),
                ]);

                throw new \Exception("Webhook delivery failed with status code: {$statusCode}");
            }
        } catch (\Exception $e) {
            $webhook->increment('failure_count');

            // Log failed delivery
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event_type' => $this->event,
                'payload' => $this->payload,
                'response_status' => 0,
                'error_message' => $e->getMessage(),
                'triggered_at' => now(),
            ]);

            Log::error('WebhookDelivery Job: Exception occurred', [
                'webhook_id' => $this->webhookId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check if URL points to internal application routes
     */
    protected function isInternalUrl(string $url): bool
    {
        $appUrl = config('app.url', 'http://localhost:8000');
        $parsedAppUrl = parse_url($appUrl);
        $parsedWebhookUrl = parse_url($url);
        
        if (!isset($parsedWebhookUrl['host']) || !isset($parsedAppUrl['host'])) {
            return false;
        }
        
        $appHost = str_replace(['www.', 'http://', 'https://'], '', $parsedAppUrl['host']);
        $webhookHost = str_replace(['www.', 'http://', 'https://'], '', $parsedWebhookUrl['host']);
        
        // Check if hosts match (localhost, 127.0.0.1, or same domain)
        $hostsMatch = $webhookHost === $appHost || 
                     ($webhookHost === 'localhost' && $appHost === 'localhost') ||
                     ($webhookHost === '127.0.0.1' && $appHost === '127.0.0.1');
        
        if (!$hostsMatch) {
            return false;
        }
        
        // Check if path contains internal routes
        $path = $parsedWebhookUrl['path'] ?? '';
        return str_starts_with($path, '/webhooks') || 
               str_starts_with($path, '/webhook/create') ||
               str_starts_with($path, '/sessions') ||
               str_starts_with($path, '/messages');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $webhook = Webhook::find($this->webhookId);
        if ($webhook) {
            $webhook->increment('failure_count');
        }

        Log::error('WebhookDelivery Job: Job failed permanently', [
            'webhook_id' => $this->webhookId,
            'error' => $exception->getMessage(),
        ]);
    }
}
