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
            'url' => 'required|url|max:500',
            'session_id' => 'nullable|exists:whatsapp_sessions,id',
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
            'url' => 'required|url|max:500',
            'session_id' => 'nullable|exists:whatsapp_sessions,id',
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
        
        // Log webhook receipt from WAHA built-in webhook
        Log::info('Built-in webhook: Received event from WAHA', [
            'session_id' => $sessionId,
            'event' => $request->input('event'),
            'payload_keys' => array_keys($request->input('payload', [])),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $session = WhatsAppSession::where('session_id', $sessionId)->first();
        if (!$session) {
            Log::warning('Webhook: Device not found', ['session_id' => $sessionId]);
            return response()->json(['error' => 'Device not found'], 404);
        }

        $event = $request->input('event');
        $payload = $request->input('payload', []);

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
            // Dispatch webhook delivery job
            WebhookDelivery::dispatch($webhook->id, $event, $payload);
            
            Log::info('Webhook delivery job dispatched', [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'event' => $event,
            ]);
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
                Log::info('Webhook: Message already exists', ['message_id' => $whatsappMessageId]);
                return;
            }

            // Extract phone numbers
            $from = $payload['from'] ?? null;
            $to = $payload['to'] ?? null;
            $participant = $payload['participant'] ?? null; // For group messages

            // Convert @s.whatsapp.net to @c.us if needed
            $fromNumber = $this->extractPhoneNumber($from);
            $toNumber = $this->extractPhoneNumber($to);

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

            Log::info('Built-in webhook: Message automatically saved to database', [
                'whatsapp_message_id' => $whatsappMessageId,
                'direction' => $direction,
                'from' => $fromNumber,
                'to' => $toNumber,
                'type' => $messageType,
                'session_id' => $session->session_id,
                'user_id' => $session->user_id,
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

            Log::info('Webhook: Message ack updated', [
                'whatsapp_message_id' => $whatsappMessageId,
                'ack' => $ack,
                'status' => $status,
            ]);
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
                // For now, we'll just log it
                Log::info('Webhook: Message reaction', [
                    'message_id' => $reactionMessageId,
                    'reaction' => $reactionText,
                ]);
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

                Log::info('Webhook: Message edited', [
                    'whatsapp_message_id' => $whatsappMessageId,
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

                Log::info('Webhook: Message revoked', [
                    'whatsapp_message_id' => $whatsappMessageId,
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
        // Log webhook receipt from WACloud
        Log::info('WACloud webhook: Received webhook', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        try {
            // Get webhook data from request
            $data = $request->all();
            
            // Log the webhook payload
            Log::info('WACloud webhook: Payload received', [
                'data' => $data,
            ]);

            // Process WACloud webhook data
            // Adjust this based on WACloud webhook format
            $event = $data['event'] ?? $data['type'] ?? 'unknown';
            $payload = $data['data'] ?? $data['payload'] ?? $data;

            Log::info('WACloud webhook: Processing event', [
                'event' => $event,
                'payload' => $payload,
            ]);

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
