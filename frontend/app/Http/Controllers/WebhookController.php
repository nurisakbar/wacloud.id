<?php

namespace App\Http\Controllers;

use App\Jobs\WebhookDelivery;
use App\Models\Message;
use App\Models\Webhook;
use App\Models\WhatsAppSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WebhookController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['receive', 'wacloud']);
    }

    public function index()
    {
        $webhooks = Auth::user()->webhooks()->with('session')->latest()->paginate(10);
        $sessions = Auth::user()->whatsappSessions()->where('status', 'connected')->get();
        return view('webhooks.index', compact('webhooks', 'sessions'));
    }

    public function create()
    {
        $sessions = Auth::user()->whatsappSessions()->where('status', 'connected')->get();
        return view('webhooks.create', compact('sessions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => [
                'required',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    // Prevent webhook URLs pointing to internal application routes
                    $appUrl = config('app.url', 'http://localhost:8000');
                    $parsedAppUrl = parse_url($appUrl);
                    $parsedWebhookUrl = parse_url($value);
                    
                    // Check if webhook URL points to the same domain as the application
                    if (isset($parsedWebhookUrl['host']) && isset($parsedAppUrl['host'])) {
                        $appHost = str_replace(['www.', 'http://', 'https://'], '', $parsedAppUrl['host']);
                        $webhookHost = str_replace(['www.', 'http://', 'https://'], '', $parsedWebhookUrl['host']);
                        
                        // Check if hosts match (localhost, 127.0.0.1, or same domain)
                        if ($webhookHost === $appHost || 
                            ($webhookHost === 'localhost' && $appHost === 'localhost') ||
                            ($webhookHost === '127.0.0.1' && $appHost === '127.0.0.1')) {
                            
                            // Check if path contains internal routes
                            $path = $parsedWebhookUrl['path'] ?? '';
                            if (str_starts_with($path, '/webhooks') || 
                                str_starts_with($path, '/webhook/create') ||
                                str_starts_with($path, '/sessions') ||
                                str_starts_with($path, '/messages')) {
                                $fail('Webhook URL tidak boleh mengarah ke route internal aplikasi. Gunakan URL eksternal atau webhook.site untuk testing.');
                            }
                        }
                    }
                },
            ],
            'session_id' => [
                'required',
                'exists:whatsapp_sessions,id',
                function ($attribute, $value, $fail) {
                    // Ensure the session belongs to the authenticated user
                    $session = WhatsAppSession::find($value);
                    if ($session && $session->user_id !== Auth::id()) {
                        $fail('Device tidak ditemukan atau tidak memiliki akses.');
                    }
                    
                    // Check if this device already has a webhook
                    $existingWebhook = Webhook::where('user_id', Auth::id())
                        ->where('session_id', $value)
                        ->where('id', '!=', request()->route('webhook')?->id) // Exclude current webhook if updating
                        ->first();
                    
                    if ($existingWebhook) {
                        $fail('Device ini sudah memiliki webhook. Setiap device hanya boleh memiliki 1 webhook.');
                    }
                },
            ],
            'events' => 'required|array',
            'events.*' => 'in:message,status,session',
            'is_active' => 'nullable|boolean',
        ]);

        Webhook::create([
            'user_id' => Auth::id(),
            'session_id' => $request->session_id,
            'name' => $request->name,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => $request->secret ? bcrypt($request->secret) : null,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        return redirect()->route('webhooks.index')->with('success', 'Webhook created successfully.');
    }

    public function show(Webhook $webhook)
    {
        if ($webhook->user_id !== Auth::id()) abort(403);
        
        // Load relationships with all necessary data
        $webhook->load([
            'session' => function($query) {
                $query->with('user');
            },
            'logs' => function($query) {
                $query->latest()->limit(50);
            }
        ]);
        
        // Get statistics
        $totalLogs = $webhook->logs()->count();
        $successLogs = $webhook->logs()->where('response_status', '>=', 200)
            ->where('response_status', '<', 300)
            ->count();
        
        return view('webhooks.show', compact('webhook', 'totalLogs', 'successLogs'));
    }

    public function edit(Webhook $webhook)
    {
        if ($webhook->user_id !== Auth::id()) abort(403);
        
        $sessions = Auth::user()->whatsappSessions()->where('status', 'connected')->get();
        return view('webhooks.edit', compact('webhook', 'sessions'));
    }

    public function update(Request $request, Webhook $webhook)
    {
        if ($webhook->user_id !== Auth::id()) abort(403);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => [
                'required',
                'url',
                'max:500',
                function ($attribute, $value, $fail) {
                    // Prevent webhook URLs pointing to internal application routes
                    $appUrl = config('app.url', 'http://localhost:8000');
                    $parsedAppUrl = parse_url($appUrl);
                    $parsedWebhookUrl = parse_url($value);
                    
                    // Check if webhook URL points to the same domain as the application
                    if (isset($parsedWebhookUrl['host']) && isset($parsedAppUrl['host'])) {
                        $appHost = str_replace(['www.', 'http://', 'https://'], '', $parsedAppUrl['host']);
                        $webhookHost = str_replace(['www.', 'http://', 'https://'], '', $parsedWebhookUrl['host']);
                        
                        // Check if hosts match (localhost, 127.0.0.1, or same domain)
                        if ($webhookHost === $appHost || 
                            ($webhookHost === 'localhost' && $appHost === 'localhost') ||
                            ($webhookHost === '127.0.0.1' && $appHost === '127.0.0.1')) {
                            
                            // Check if path contains internal routes
                            $path = $parsedWebhookUrl['path'] ?? '';
                            if (str_starts_with($path, '/webhooks') || 
                                str_starts_with($path, '/webhook/create') ||
                                str_starts_with($path, '/sessions') ||
                                str_starts_with($path, '/messages')) {
                                $fail('Webhook URL tidak boleh mengarah ke route internal aplikasi. Gunakan URL eksternal atau webhook.site untuk testing.');
                            }
                        }
                    }
                },
            ],
            'session_id' => [
                'required',
                'exists:whatsapp_sessions,id',
                function ($attribute, $value, $fail) use ($webhook) {
                    // Ensure the session belongs to the authenticated user
                    $session = WhatsAppSession::find($value);
                    if ($session && $session->user_id !== Auth::id()) {
                        $fail('Device tidak ditemukan atau tidak memiliki akses.');
                    }
                    
                    // Check if this device already has a webhook (excluding current webhook)
                    $existingWebhook = Webhook::where('user_id', Auth::id())
                        ->where('session_id', $value)
                        ->where('id', '!=', $webhook->id)
                        ->first();
                    
                    if ($existingWebhook) {
                        $fail('Device ini sudah memiliki webhook. Setiap device hanya boleh memiliki 1 webhook.');
                    }
                },
            ],
            'events' => 'required|array',
            'events.*' => 'in:message,status,session',
            'secret' => 'nullable|string|min:16',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = [
            'name' => $request->name,
            'url' => $request->url,
            'session_id' => $request->session_id,
            'events' => $request->events,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : false,
        ];

        // Only update secret if provided
        if ($request->filled('secret')) {
            $updateData['secret'] = bcrypt($request->secret);
        }

        $webhook->update($updateData);
        
        return redirect()->route('webhooks.show', $webhook)->with('success', 'Webhook berhasil diperbarui.');
    }

    public function destroy(Webhook $webhook)
    {
        if ($webhook->user_id !== Auth::id()) abort(403);
        $webhook->delete();
        return redirect()->route('webhooks.index')->with('success', 'Webhook deleted successfully.');
    }

    /**
     * Test webhook by sending a test payload
     */
    public function test(Request $request, Webhook $webhook)
    {
        if ($webhook->user_id !== Auth::id()) abort(403);
        
        $request->validate([
            'event_type' => 'required|in:message,message.ack,status,session',
            'message_body' => 'nullable|string|max:500',
        ]);

        try {
            // Prepare test payload based on event type
            $payload = $this->prepareTestPayload($request->event_type, $request->message_body, $webhook);
            
            // Send webhook using the same job as real webhooks
            WebhookDelivery::dispatch($webhook->id, $request->event_type, $payload);
            
            return response()->json([
                'success' => true,
                'message' => 'Test webhook berhasil dikirim. Cek webhook logs untuk melihat hasilnya.',
            ]);
        } catch (\Exception $e) {
            Log::error('Test webhook failed', [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim test webhook: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Prepare test payload based on event type
     */
    protected function prepareTestPayload(string $eventType, ?string $messageBody, Webhook $webhook): array
    {
        $sessionId = $webhook->session ? $webhook->session->session_id : 'test_session';
        $timestamp = now()->timestamp;
        
        switch ($eventType) {
            case 'message':
                return [
                    'event' => 'message',
                    'session' => $sessionId,
                    'payload' => [
                        'id' => 'test_' . Str::random(20),
                        'timestamp' => $timestamp,
                        'from' => '6281234567890@c.us',
                        'fromMe' => false,
                        'to' => '6289876543210@c.us',
                        'body' => $messageBody ?? 'Test pesan dari simulasi webhook manual',
                        'hasMedia' => false,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ];
                
            case 'message.ack':
                return [
                    'event' => 'message.ack',
                    'session' => $sessionId,
                    'payload' => [
                        'id' => 'test_' . Str::random(20),
                        'ack' => 2, // Read
                        'timestamp' => $timestamp,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ];
                
            case 'status':
                return [
                    'event' => 'status',
                    'session' => $sessionId,
                    'payload' => [
                        'status' => 'connected',
                        'timestamp' => $timestamp,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ];
                
            case 'session':
                return [
                    'event' => 'session',
                    'session' => $sessionId,
                    'payload' => [
                        'status' => 'connected',
                        'timestamp' => $timestamp,
                    ],
                    'timestamp' => now()->toIso8601String(),
                ];
                
            default:
                throw new \InvalidArgumentException('Invalid event type');
        }
    }

    public function receive(Request $request, $sessionId)
    {
        /**
         * Built-in Webhook Receiver - Automatically receives messages from WAHA
         * This is the MAIN FEATURE - no manual setup needed
         * Webhook is automatically configured when session is created
         * 
         * Endpoint: POST /webhook/receive/{sessionId}
         */
        
        $session = WhatsAppSession::where('session_id', $sessionId)->first();
        if (!$session) {
            Log::warning('Webhook: Device not found', ['session_id' => $sessionId]);
            return response()->json(['error' => 'Device not found'], 404);
        }

        $event = $request->input('event');
        $payload = $request->input('payload', []);
        
        // Log webhook receipt for debugging
        Log::debug('Webhook: Received event', [
            'session_id' => $sessionId,
            'session_db_id' => $session->id,
            'session_name' => $session->session_name,
            'session_phone' => $session->phone_number,
            'event' => $event,
            'user_id' => $session->user_id,
        ]);

        // ============================================
        // BUILT-IN WEBHOOK: Auto-save messages to database
        // This is the main feature - automatically receive and save incoming messages
        // No manual webhook setup needed - it's configured automatically when session is created
        // ============================================
        
        // Handle message events - AUTOMATICALLY save both incoming and outgoing messages to database
        if (in_array($event, ['message', 'message.any']) && !empty($payload)) {
            $this->handleMessage($session, $payload);
        }

        // Handle message.ack events - update message status
        if ($event === 'message.ack' && !empty($payload)) {
            $this->handleMessageAck($session, $payload);
        }

        // Handle message.reaction events
        if ($event === 'message.reaction' && !empty($payload)) {
            $this->handleMessageReaction($session, $payload);
        }

        // Handle message.edited events
        if ($event === 'message.edited' && !empty($payload)) {
            $this->handleMessageEdited($session, $payload);
        }

        // Handle message.revoked events
        if ($event === 'message.revoked' && !empty($payload)) {
            $this->handleMessageRevoked($session, $payload);
        }

        // ============================================
        // USER WEBHOOKS: Forward to user's custom webhooks (optional)
        // This is separate from built-in webhook above
        // User webhooks are for forwarding events to external applications
        // ============================================
        
        // Forward to user's custom webhooks using jobs (if user has configured any)
        $webhooks = Webhook::where('user_id', $session->user_id)
            ->where('is_active', true)
            ->where(function($q) use ($session) {
                $q->whereNull('session_id')->orWhere('session_id', $session->id);
            })
            ->where(function($q) use ($event) {
                $q->whereJsonContains('events', $event)
                  ->orWhereJsonContains('events', 'message'); // Also forward if webhook listens to all messages
            })
            ->get();

        // Prepare payload for webhook
        $payload = [
            'event' => $event,
            'session' => $sessionId,
            'payload' => $payload,
            'timestamp' => now()->toIso8601String(),
        ];

        foreach ($webhooks as $webhook) {
            // Dispatch webhook delivery job (using default queue)
            WebhookDelivery::dispatch($webhook->id, $event, $payload);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle message from WAHA webhook (both incoming and outgoing)
     */
    protected function handleMessage(WhatsAppSession $session, array $payload)
    {
        try {
            // Determine if message is incoming or outgoing
            $isFromMe = !empty($payload['fromMe']) && $payload['fromMe'] === true;
            $direction = $isFromMe ? 'outgoing' : 'incoming';

            // Extract WhatsApp message ID - handle both string and object formats
            $whatsappMessageId = $this->extractMessageId($payload['id'] ?? null);
            if (!$whatsappMessageId) {
                Log::warning('Webhook: Message ID not found in payload', ['payload' => $payload]);
                return;
            }

            // Check if message already exists
            $existingMessage = Message::where('whatsapp_message_id', $whatsappMessageId)
                ->where('session_id', $session->id)
                ->first();

            if ($existingMessage) {
                return;
            }

            // Extract phone numbers
            $from = $payload['from'] ?? null;
            $to = $payload['to'] ?? null;
            $participant = $payload['participant'] ?? null; // For group messages

            // Convert @s.whatsapp.net to @c.us if needed
            $fromNumber = $this->extractPhoneNumber($from);
            $toNumber = $this->extractPhoneNumber($to);
            
            // For incoming messages, if to_number is null, use session's phone number
            // For outgoing messages, if from_number is null, use session's phone number
            
            // Helper function to get session phone number
            $getSessionPhoneNumber = function() use ($session) {
                // Try phone_number field first
                if ($session->phone_number) {
                    return $this->extractPhoneNumber($session->phone_number);
                }
                
                // Try device_info['phone']
                if (!empty($session->device_info['phone'])) {
                    return $this->extractPhoneNumber($session->device_info['phone']);
                }
                
                // Try device_info['wid'] (format: 6281234567890@c.us)
                if (!empty($session->device_info['wid'])) {
                    $wid = $session->device_info['wid'];
                    if (is_string($wid) && strpos($wid, '@') !== false) {
                        return $this->extractPhoneNumber($wid);
                    }
                }
                
                return null;
            };
            
            $sessionPhoneNumber = $getSessionPhoneNumber();
            
            if ($isFromMe) {
                // Outgoing message: from_number should be session's phone number
                if (!$fromNumber && $sessionPhoneNumber) {
                    $fromNumber = $sessionPhoneNumber;
                }
                
                // Validate: For outgoing messages, from_number must match session's phone number
                if ($sessionPhoneNumber && $fromNumber && $fromNumber !== $sessionPhoneNumber) {
                    Log::warning('Webhook: Message rejected - from_number does not match session', [
                        'session_id' => $session->session_id,
                        'session_phone' => $sessionPhoneNumber,
                        'message_from' => $fromNumber,
                        'whatsapp_message_id' => $whatsappMessageId,
                    ]);
                    return; // Reject message - not for this session
                }
            } else {
                // Incoming message: to_number should be session's phone number
                if (!$toNumber && $sessionPhoneNumber) {
                    $toNumber = $sessionPhoneNumber;
                }
                // Also try to extract from _data if available (might contain recipient info)
                if (!$toNumber && !empty($payload['_data']['key']['remoteJidAlt'])) {
                    $toNumber = $this->extractPhoneNumber($payload['_data']['key']['remoteJidAlt']);
                }
                
                // Validate: For incoming messages, to_number must match session's phone number
                if ($sessionPhoneNumber && $toNumber && $toNumber !== $sessionPhoneNumber) {
                    Log::warning('Webhook: Message rejected - to_number does not match session', [
                        'session_id' => $session->session_id,
                        'session_phone' => $sessionPhoneNumber,
                        'message_to' => $toNumber,
                        'whatsapp_message_id' => $whatsappMessageId,
                        'from_number' => $fromNumber,
                    ]);
                    return; // Reject message - not for this session
                }
            }

            // Determine message type
            $messageType = $this->determineMessageType($payload);
            
            // Extract content
            $content = $payload['body'] ?? null;
            $mediaUrl = null;
            $mediaMimeType = null;
            $mediaSize = null;
            $caption = null;

            if (!empty($payload['hasMedia']) && !empty($payload['media'])) {
                $media = $payload['media'];
                $mediaUrl = $media['url'] ?? null;
                $mediaMimeType = $media['mimetype'] ?? null;
                $mediaSize = $media['fileLength'] ?? $media['size'] ?? null;
                
                // For documents, get filename
                if ($messageType === 'document' && !empty($media['filename'])) {
                    // Filename is already in media array
                }
                
                // Caption might be in body if hasMedia is true
                if (!empty($payload['body']) && $messageType !== 'text') {
                    $caption = $payload['body'];
                }
                
                // If media URL is relative, make it absolute using WAHA base URL
                if ($mediaUrl && !filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                    $wahaBaseUrl = $session->waha_instance_url ?? config('services.waha.base_url', 'http://localhost:3000');
                    $mediaUrl = rtrim($wahaBaseUrl, '/') . '/' . ltrim($mediaUrl, '/');
                }
            }

            // Handle special message types
            if ($messageType === 'poll' && !empty($payload['poll'])) {
                $content = json_encode($payload['poll']);
            } elseif ($messageType === 'button' && !empty($payload['buttons'])) {
                $content = json_encode([
                    'body' => $payload['body'] ?? '',
                    'buttons' => $payload['buttons'] ?? [],
                ]);
            } elseif ($messageType === 'list' && !empty($payload['list'])) {
                $content = json_encode($payload['list']);
            }

            // Parse timestamp
            $timestamp = $payload['timestamp'] ?? time();
            if (is_float($timestamp)) {
                $createdAt = Carbon::createFromTimestamp($timestamp);
            } else {
                $createdAt = Carbon::parse($timestamp);
            }

            // Determine status based on direction
            // For incoming messages, they're considered delivered
            // For outgoing messages, start with pending (will be updated by message.ack)
            $status = $isFromMe ? 'pending' : 'delivered';

            // Validate that we have required phone numbers
            if (!$fromNumber) {
                Log::warning('Webhook: Cannot determine from_number for message', [
                    'whatsapp_message_id' => $whatsappMessageId,
                    'direction' => $direction,
                    'payload' => $payload,
                ]);
                return;
            }
            
            if (!$toNumber) {
                Log::warning('Webhook: Cannot determine to_number for message', [
                    'whatsapp_message_id' => $whatsappMessageId,
                    'direction' => $direction,
                    'payload' => $payload,
                    'session_phone_number' => $session->phone_number,
                ]);
                return;
            }

            // Create message record
            Message::create([
                'user_id' => $session->user_id,
                'session_id' => $session->id,
                'whatsapp_message_id' => $whatsappMessageId,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'message_type' => $messageType,
                'content' => $content,
                'media_url' => $mediaUrl,
                'media_mime_type' => $mediaMimeType,
                'media_size' => $mediaSize,
                'caption' => $caption,
                'direction' => $direction,
                'status' => $status,
                'sent_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook: Error handling incoming message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle message acknowledgment (ack) events
     */
    protected function handleMessageAck(WhatsAppSession $session, array $payload)
    {
        try {
            // Extract WhatsApp message ID - handle both string and object formats
            $whatsappMessageId = $this->extractMessageId($payload['id'] ?? null);
            if (!$whatsappMessageId) {
                return;
            }

            $message = Message::where('whatsapp_message_id', $whatsappMessageId)
                ->where('session_id', $session->id)
                ->where('direction', 'outgoing')
                ->first();

            if (!$message) {
                return;
            }

            $ack = $payload['ack'] ?? null;
            $status = $message->status;

            // Update status based on ack value
            // ack: 0 = pending, 1 = delivered, 2 = read, 3 = played
            if ($ack === 1 && $status !== 'read') {
                $status = 'delivered';
                $message->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);
            } elseif ($ack === 2) {
                $status = 'read';
                $message->update([
                    'status' => 'read',
                    'read_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook: Error handling message ack', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Extract WhatsApp message ID from payload (handles both string and object formats)
     */
    protected function extractMessageId($idData): ?string
    {
        if (!$idData) {
            return null;
        }

        // Handle ID as object/array (WAHA format: {"fromMe":false,"remote":"...","id":"..."})
        if (is_array($idData)) {
            $messageId = $idData['id'] ?? $idData['_serialized'] ?? null;
            // If still not found, try to construct from available data
            if (!$messageId && isset($idData['remote']) && isset($idData['id'])) {
                $messageId = $idData['id'];
            }
            // Fallback: use remote + id combination
            if (!$messageId && isset($idData['remote'])) {
                $messageId = ($idData['remote'] ?? '') . '_' . ($idData['id'] ?? uniqid());
            }
        } else {
            $messageId = $idData;
        }

        return $messageId ? (string) $messageId : null;
    }

    /**
     * Extract phone number from WAHA format
     */
    protected function extractPhoneNumber(?string $chatId): ?string
    {
        if (!$chatId) {
            return null;
        }

        // Remove @c.us, @s.whatsapp.net, @g.us, @lid, @newsletter
        $number = preg_replace('/@.*$/', '', $chatId);
        
        // Remove + if present
        $number = ltrim($number, '+');
        
        return $number ?: null;
    }

    /**
     * Determine message type from WAHA payload
     */
    protected function determineMessageType(array $payload): string
    {
        // Check for specific message types
        if (!empty($payload['poll'])) {
            return 'poll';
        }
        
        if (!empty($payload['buttons']) || !empty($payload['interactiveMessage'])) {
            return 'button';
        }
        
        if (!empty($payload['list'])) {
            return 'list';
        }

        if (!empty($payload['location'])) {
            return 'location';
        }

        if (!empty($payload['contact'])) {
            return 'contact';
        }

        if (!empty($payload['sticker'])) {
            return 'sticker';
        }

        // Check media type
        if (!empty($payload['hasMedia']) && !empty($payload['media'])) {
            $mimetype = $payload['media']['mimetype'] ?? '';
            
            if (strpos($mimetype, 'image/') === 0) {
                return 'image';
            }
            
            if (strpos($mimetype, 'video/') === 0) {
                return 'video';
            }
            
            if (strpos($mimetype, 'audio/') === 0 || strpos($mimetype, 'voice') !== false) {
                return 'voice';
            }
            
            // Default to document for other media types
            return 'document';
        }

        // Default to text
        return 'text';
    }

    /**
     * Handle message reaction event
     */
    protected function handleMessageReaction(WhatsAppSession $session, array $payload)
    {
        try {
            $reactionMessageId = $this->extractMessageId($payload['reaction']['messageId'] ?? null);
            if (!$reactionMessageId) {
                return;
            }

            // Find the message that was reacted to
            $message = Message::where('whatsapp_message_id', $reactionMessageId)
                ->where('session_id', $session->id)
                ->first();

            if ($message) {
                $reactionText = $payload['reaction']['text'] ?? '';
                // You can store reaction in a separate table or add a reactions column
                // For now, we'll just skip logging
            }
        } catch (\Exception $e) {
            Log::error('Webhook: Error handling message reaction', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle message edited event
     */
    protected function handleMessageEdited(WhatsAppSession $session, array $payload)
    {
        try {
            $whatsappMessageId = $this->extractMessageId($payload['id'] ?? null);
            if (!$whatsappMessageId) {
                return;
            }

            $message = Message::where('whatsapp_message_id', $whatsappMessageId)
                ->where('session_id', $session->id)
                ->first();

            if ($message) {
                // Update message content with edited version
                $newContent = $payload['body'] ?? $message->content;
                $message->update([
                    'content' => $newContent,
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook: Error handling message edited', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle message revoked event
     */
    protected function handleMessageRevoked(WhatsAppSession $session, array $payload)
    {
        try {
            $beforeMessage = $payload['before'] ?? null;
            if (!$beforeMessage) {
                return;
            }

            $whatsappMessageId = $this->extractMessageId($beforeMessage['id'] ?? null);
            if (!$whatsappMessageId) {
                return;
            }
            $message = Message::where('whatsapp_message_id', $whatsappMessageId)
                ->where('session_id', $session->id)
                ->first();

            if ($message) {
                // Clear message content (revoked)
                $message->update([
                    'content' => '[Pesan telah dihapus]',
                    'status' => 'revoked',
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook: Error handling message revoked', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Handle webhook from WACloud
     * Endpoint: POST /webhook/wacloud
     */
    public function wacloud(Request $request)
    {
        try {
            // Get webhook data from request
            $data = $request->all();

            // Process WACloud webhook data
            // Adjust this based on WACloud webhook format
            $event = $data['event'] ?? $data['type'] ?? 'unknown';
            $payload = $data['data'] ?? $data['payload'] ?? $data;

            // TODO: Process WACloud webhook events here
            // Examples:
            // - Message status updates
            // - Delivery receipts
            // - Read receipts
            // - Session status changes

            return response()->json([
                'success' => true,
                'message' => 'Webhook received successfully',
            ], 200);

        } catch (\Exception $e) {
            Log::error('WACloud webhook: Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error processing webhook: ' . $e->getMessage(),
            ], 500);
        }
    }
}
