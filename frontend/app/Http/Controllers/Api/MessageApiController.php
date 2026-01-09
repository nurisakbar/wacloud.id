<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PhoneNumberHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendMessageRequest;
use App\Jobs\SendMessage as SendMessageJob;
use App\Models\Message;
use App\Models\MessagePricingSetting;
use App\Models\QuotaUsageLog;
use App\Models\Template;
use App\Models\UserQuota;
use App\Models\WhatsAppSession;
use App\Services\ApiUsageService;
use App\Services\TemplateService;
use App\Services\WatermarkService;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageApiController extends Controller
{
    protected WahaService $wahaService;
    protected ApiUsageService $usageService;
    protected TemplateService $templateService;

    public function __construct(WahaService $wahaService, ApiUsageService $usageService, TemplateService $templateService)
    {
        $this->wahaService = $wahaService;
        $this->usageService = $usageService;
        $this->templateService = $templateService;
    }

    /**
     * Send message (text, image, or document)
     */
    public function store(SendMessageRequest $request, $session = null)
    {
        $startTime = microtime(true);
        
        // Support both formats:
        // 1. /api/v1/messages (device_id in body)
        // 2. /api/v1/devices/{session}/messages (device in URL)
        
        // If session is provided in URL, use it; otherwise use device_id from request body
        $sessionId = $session ?? $request->device_id;
        
        if (!$sessionId) {
            $this->usageService->log($request, 400, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device ID is required (either in URL or request body as device_id)',
            ], 400);
        }

        // Get session
        $session = WhatsAppSession::where('session_id', $sessionId)
            ->where('user_id', $request->user->id)
            ->where('status', 'connected')
            ->first();

        if (!$session) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device not found or not connected',
            ], 404);
        }

        // Determine chat type (personal or group)
        $chatType = $request->input('chat_type', 'personal'); // Default to personal
        
        // Handle chat ID based on type
        if ($chatType === 'group') {
            // For group, the 'to' field should be the group ID (can be full format or just ID)
            $toValue = $request->to;
            
            // If already contains @g.us, use as is
            if (strpos($toValue, '@g.us') !== false) {
                $chatId = $toValue;
            } else {
                // Otherwise, append @g.us
                $chatId = $toValue . '@g.us';
            }
            
            // For groups, we don't normalize as phone number
            $normalizedNumber = $toValue;
        } else {
            // For personal chat, normalize phone number
            $normalizedNumber = PhoneNumberHelper::normalize($request->to);
            if (!$normalizedNumber) {
                $this->usageService->log($request, 400, $startTime);
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid phone number format',
                ], 400);
            }
            
            $chatId = $normalizedNumber . '@c.us';
        }

        try {
            $template = null;
            $processedTemplate = null;
            
            // Handle template if provided
            if ($request->template_id) {
                $template = Template::where('id', $request->template_id)
                    ->where('user_id', $request->user->id)
                    ->where('is_active', true)
                    ->first();

                if (!$template) {
                    $this->usageService->log($request, 404, $startTime);
                    return response()->json([
                        'success' => false,
                        'error' => 'Template not found or inactive',
                    ], 404);
                }

                // Validate variables
                $variables = $request->input('variables', []);
                $validation = $this->templateService->validateVariables($template, $variables);
                
                if (!$validation['valid']) {
                    $this->usageService->log($request, 400, $startTime);
                    return response()->json([
                        'success' => false,
                        'error' => 'Missing required variables',
                        'missing_variables' => $validation['missing'],
                    ], 400);
                }

                // Process template
                $processedTemplate = $this->templateService->processTemplate($template, $variables);
            }

            // Use template content if template is provided
            $messageType = $template ? $processedTemplate['message_type'] : $request->message_type;

            // Get pricing settings and quota
            $pricing = MessagePricingSetting::getActive();
            $watermarkService = app(WatermarkService::class);
            $userQuota = UserQuota::getOrCreateForUser($request->user->id);
            
            // Variables for quota tracking
            $quotaDeducted = false;
            $quotaType = null;
            $quotaAmount = 0;
            
            // Prepare message data and validate/deduct quota based on message type
            $messageData = [
                'user_id' => $request->user->id,
                'session_id' => $session->id,
                'from_number' => null,
                'to_number' => $normalizedNumber,
                'chat_type' => $chatType,
                'message_type' => $messageType,
                'direction' => 'outgoing',
                'status' => 'pending', // Will be updated by job
            ];

            // Prepare job parameters
            $jobParams = [
                'sessionId' => $session->id,
                'chatId' => $chatId,
                'messageType' => $messageType,
                'content' => null,
                'imageUrl' => null,
                'videoUrl' => null,
                'documentUrl' => null,
                'caption' => null,
                'pollName' => null,
                'pollOptions' => null,
                'multipleAnswers' => null,
                'fallbackToText' => null,
                'buttons' => null,
                'header' => null,
                'footer' => null,
                'headerImage' => null,
                'listMessage' => null,
                'replyTo' => null,
                'filename' => null,
                'asNote' => null,
                'convert' => null,
                'chatType' => $chatType,
            ];

            // Handle different message types - validate quota and prepare data
            switch ($messageType) {
                case 'text':
                    // Support both 'text' and 'message' fields, or use template content
                    if ($template) {
                        $textContent = $processedTemplate['content'];
                    } else {
                        $textContent = $request->input('text') ?? $request->input('message');
                    }
                    
                    // Determine if should use watermark (free) or premium
                    $watermarkPrice = $pricing->getPriceForMessageType('text', true);
                    $premiumPrice = $pricing->getPriceForMessageType('text', false);
                    
                    // PRIORITAS: 1. text_quota (non-watermark), 2. free_text_quota (watermark), 3. balance
                    // Validate quota availability and prepare content
                    $finalContent = $textContent;
                    if ($userQuota->text_quota > 0) {
                        $quotaDeducted = true;
                        $quotaType = 'text_quota';
                        $quotaAmount = 1;
                        // No watermark needed
                        $finalContent = $textContent;
                    } elseif ($watermarkPrice == 0 && $userQuota->hasFreeTextQuota(1)) {
                        $quotaDeducted = true;
                        $quotaType = 'free_text_quota';
                        $quotaAmount = 1;
                        // Add watermark for free text quota
                        $finalContent = $watermarkService->addWatermark($textContent, $pricing->watermark_text);
                    } elseif ($userQuota->hasEnoughBalance($premiumPrice)) {
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $premiumPrice;
                        // No watermark needed
                        $finalContent = $textContent;
                    } else {
                        throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                    }
                    
                    $jobParams['content'] = $finalContent;
                    $messageData['content'] = $finalContent;
                    break;

                case 'image':
                    // Handle template for image messages
                    if ($template) {
                        $imageUrl = $processedTemplate['metadata']['image_url'] ?? $request->image_url;
                        $caption = $processedTemplate['content'] ?? $request->caption;
                    } else {
                        $imageUrl = $request->image_url;
                        $caption = $request->caption;
                    }
                    
                    // Multimedia message - validate quota
                    $price = $pricing->getPriceForMessageType('image');
                    
                    if ($userQuota->multimedia_quota > 0) {
                        $quotaDeducted = true;
                        $quotaType = 'multimedia_quota';
                        $quotaAmount = 1;
                    } elseif ($userQuota->hasEnoughBalance($price)) {
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $price;
                    } else {
                        throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                    }
                    
                    $jobParams['imageUrl'] = $imageUrl;
                    $jobParams['caption'] = $caption;
                    $messageData['media_url'] = $imageUrl;
                    $messageData['caption'] = $caption;
                    break;

                case 'video':
                    // Multimedia message - validate quota
                    $price = $pricing->getPriceForMessageType('video');
                    
                    if ($userQuota->multimedia_quota > 0) {
                        $quotaDeducted = true;
                        $quotaType = 'multimedia_quota';
                        $quotaAmount = 1;
                    } elseif ($userQuota->hasEnoughBalance($price)) {
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $price;
                    } else {
                        throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                    }
                    
                    $jobParams['videoUrl'] = $request->video_url;
                    $jobParams['caption'] = $request->caption;
                    $jobParams['asNote'] = $request->as_note ?? false;
                    $jobParams['convert'] = $request->convert ?? false;
                    $messageData['media_url'] = $request->video_url;
                    $messageData['caption'] = $request->caption;
                    break;

                case 'document':
                    // Multimedia message - validate quota
                    $price = $pricing->getPriceForMessageType('document');
                    
                    if ($userQuota->multimedia_quota > 0) {
                        $quotaDeducted = true;
                        $quotaType = 'multimedia_quota';
                        $quotaAmount = 1;
                    } elseif ($userQuota->hasEnoughBalance($price)) {
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $price;
                    } else {
                        throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                    }
                    
                    $jobParams['documentUrl'] = $request->document_url;
                    $jobParams['filename'] = $request->filename;
                    $jobParams['caption'] = $request->caption;
                    $messageData['media_url'] = $request->document_url;
                    $messageData['caption'] = $request->caption;
                    break;

                case 'poll':
                    // Poll messages don't charge quota (free)
                    $jobParams['pollName'] = $request->poll_name;
                    $jobParams['pollOptions'] = $request->poll_options;
                    $jobParams['multipleAnswers'] = $request->multiple_answers ?? false;
                    $jobParams['fallbackToText'] = $request->fallback_to_text ?? false;
                    $messageData['content'] = json_encode([
                        'poll_name' => $request->poll_name,
                        'options' => $request->poll_options,
                        'multiple_answers' => $request->multiple_answers ?? false,
                    ]);
                    break;
                    
                case 'button':
                    // Handle template for button messages
                    if ($template) {
                        $metadata = $processedTemplate['metadata'] ?? [];
                        $body = $processedTemplate['content'] ?? $request->body;
                        $buttons = $metadata['buttons'] ?? $request->buttons;
                        $header = $metadata['header'] ?? $request->header;
                        $footer = $metadata['footer'] ?? $request->footer;
                        $headerImage = $metadata['header_image'] ?? $request->header_image ?? $request->headerImage;
                    } else {
                        $body = $request->body;
                        $buttons = $request->buttons;
                        $header = $request->header;
                        $footer = $request->footer;
                        $headerImage = $request->header_image ?? $request->headerImage;
                    }
                    
                    // Button messages don't charge quota (free)
                    $jobParams['content'] = $body;
                    $jobParams['buttons'] = $buttons;
                    $jobParams['header'] = $header;
                    $jobParams['footer'] = $footer;
                    $jobParams['headerImage'] = $headerImage;
                    $jobParams['fallbackToText'] = $request->fallback_to_text ?? false;
                    $messageData['content'] = json_encode([
                        'body' => $body,
                        'buttons' => $buttons,
                        'header' => $header,
                        'footer' => $footer,
                        'header_image' => $headerImage,
                    ]);
                    break;

                case 'list':
                    // Handle template for list messages
                    if ($template) {
                        $metadata = $processedTemplate['metadata'] ?? [];
                        $listMessage = $metadata['message'] ?? $request->message;
                        $replyTo = $metadata['reply_to'] ?? $request->reply_to;
                    } else {
                        $listMessage = $request->message;
                        $replyTo = $request->reply_to;
                    }
                    
                    // List messages don't charge quota (free)
                    $jobParams['listMessage'] = $listMessage;
                    $jobParams['replyTo'] = $replyTo;
                    $messageData['content'] = json_encode([
                        'message' => $listMessage,
                        'reply_to' => $replyTo,
                    ]);
                    break;
            }

            // Create message record first
            $message = Message::create($messageData);
            $messageId = $message->id;

            // Deduct quota if needed (for messages that charge quota)
            if ($quotaDeducted && $quotaType && $quotaAmount > 0) {
                // Deduct quota
                if ($quotaType === 'text_quota') {
                    if (!$userQuota->deductTextQuota(1)) {
                        throw new \Exception('Insufficient text quota');
                    }
                } elseif ($quotaType === 'free_text_quota') {
                    if (!$userQuota->deductFreeTextQuota(1)) {
                        throw new \Exception('Insufficient free text quota');
                    }
                } elseif ($quotaType === 'multimedia_quota') {
                    if (!$userQuota->deductMultimediaQuota(1)) {
                        throw new \Exception('Insufficient multimedia quota');
                    }
                } elseif ($quotaType === 'balance') {
                    if (!$userQuota->deductBalance($quotaAmount)) {
                        throw new \Exception('Insufficient balance');
                    }
                }

                // Log quota usage
                QuotaUsageLog::create([
                    'user_id' => $request->user->id,
                    'message_id' => $messageId,
                    'quota_type' => $quotaType,
                    'amount' => $quotaAmount,
                    'message_type' => $messageType,
                    'description' => $this->getQuotaDescription($messageType, $quotaType),
                ]);
            }

            // Dispatch job to send message asynchronously
            SendMessageJob::dispatch(
                $messageId,
                $jobParams['sessionId'],
                $jobParams['chatId'],
                $jobParams['messageType'],
                $jobParams['content'],
                null, // mediaPath
                null, // documentPath
                $jobParams['documentUrl'],
                $jobParams['imageUrl'],
                $jobParams['videoUrl'],
                $jobParams['caption'],
                $jobParams['chatType'],
                $jobParams['pollName'],
                $jobParams['pollOptions'],
                $jobParams['multipleAnswers'],
                $jobParams['fallbackToText'],
                $jobParams['buttons'],
                $jobParams['header'],
                $jobParams['footer'],
                $jobParams['headerImage'],
                $jobParams['listMessage'],
                $jobParams['replyTo'],
                $jobParams['filename'],
                $jobParams['asNote'],
                $jobParams['convert'],
                $quotaDeducted, // quotaAlreadyDeducted
                $quotaType,
                $quotaAmount
            );

            \Log::info('API: Message job dispatched', [
                'message_id' => $messageId,
                'session_id' => $session->session_id,
                'chat_id' => $chatId,
                'user_id' => $request->user->id,
                'message_type' => $messageType,
                'quota_type' => $quotaType,
            ]);

            $this->usageService->log($request, 201, $startTime);

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $messageId,
                    'status' => 'pending',
                    'to' => $normalizedNumber,
                    'message' => 'Message queued for sending',
                ],
            ], 201);

        } catch (\Exception $e) {
            // If quota was deducted but exception occurred before job dispatch, refund it
            if (isset($quotaDeducted) && $quotaDeducted && isset($quotaType) && isset($quotaAmount) && isset($userQuota)) {
                try {
                    $this->refundQuota($userQuota, $quotaType, $quotaAmount);
                } catch (\Exception $refundException) {
                    \Log::error('API: Failed to refund quota', [
                        'error' => $refundException->getMessage(),
                        'quota_type' => $quotaType,
                        'quota_amount' => $quotaAmount,
                    ]);
                }
            }
            
            $this->usageService->log($request, 500, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refund quota to user
     */
    protected function refundQuota(UserQuota $userQuota, string $quotaType, float $quotaAmount, ?string $messageId = null): void
    {
        if ($quotaType === 'free_text_quota') {
            $userQuota->addFreeTextQuota($quotaAmount);
            if ($messageId) {
                QuotaUsageLog::where('quota_type', 'free_text_quota')
                    ->where('message_id', $messageId)
                    ->delete();
            }
        } elseif ($quotaType === 'text_quota') {
            $userQuota->addTextQuota($quotaAmount);
            if ($messageId) {
                QuotaUsageLog::where('quota_type', 'text_quota')
                    ->where('message_id', $messageId)
                    ->delete();
            }
        } elseif ($quotaType === 'multimedia_quota') {
            $userQuota->addMultimediaQuota($quotaAmount);
            if ($messageId) {
                QuotaUsageLog::where('quota_type', 'multimedia_quota')
                    ->where('message_id', $messageId)
                    ->delete();
            }
        } elseif ($quotaType === 'balance') {
            $userQuota->addBalance($quotaAmount);
            if ($messageId) {
                QuotaUsageLog::where('quota_type', 'balance')
                    ->where('message_id', $messageId)
                    ->delete();
            }
        }
        
        \Log::info('API: Quota refunded', [
            'quota_type' => $quotaType,
            'amount' => $quotaAmount,
            'message_id' => $messageId,
        ]);
    }

    /**
     * Get quota description for logging
     */
    protected function getQuotaDescription(string $messageType, string $quotaType): string
    {
        $descriptions = [
            'free_text_quota' => 'Free text message with watermark',
            'text_quota' => 'Premium text message (without watermark)',
            'multimedia_quota' => ucfirst($messageType) . ' message',
            'balance' => ucfirst($messageType) . ' message paid with balance',
        ];
        
        return $descriptions[$quotaType] ?? ucfirst($messageType) . ' message';
    }

    /**
     * Get messages for a session
     */
    public function index(Request $request, $session = null)
    {
        $startTime = microtime(true);
        
        // Support both formats:
        // 1. /api/v1/messages?device_id=xxx (device_id in query)
        // 2. /api/v1/devices/{session}/messages (device in URL)
        
        $sessionId = $session ?? $request->input('device_id');
        
        if (!$sessionId) {
            $this->usageService->log($request, 400, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Device ID is required (either in URL or query parameter as device_id)',
            ], 400);
        }
        
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

        $messages = Message::where('session_id', $session->id)
            ->where('user_id', $request->user->id)
            ->latest()
            ->paginate($request->get('per_page', 20));

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Get message details
     */
    public function show(Request $request, $messageId)
    {
        $startTime = microtime(true);
        
        $message = Message::where('id', $messageId)
            ->where('user_id', $request->user->id)
            ->first();

        if (!$message) {
            $this->usageService->log($request, 404, $startTime);
            return response()->json([
                'success' => false,
                'error' => 'Message not found',
            ], 404);
        }

        $this->usageService->log($request, 200, $startTime);

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Sync messages from WAHA API to database
     */
    public function sync(Request $request, $sessionId)
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

        try {
            $chatId = $request->input('chatId');
            $limit = $request->input('limit', 100);

            \Log::info('API: Syncing messages from WAHA', [
                'session_id' => $session->session_id,
                'chatId' => $chatId,
                'limit' => $limit,
                'user_id' => $request->user->id,
            ]);

            $result = $this->wahaService->getMessages($session->session_id, $chatId, $limit);

            if (!$result['success']) {
                $this->usageService->log($request, 500, $startTime);
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to sync messages',
                ], 500);
            }

            $messages = $result['data'] ?? [];
            $syncedCount = 0;
            $skippedCount = 0;

            foreach ($messages as $wahaMessage) {
                try {
                    // Skip if message is from me (outgoing messages are handled separately)
                    if (!empty($wahaMessage['fromMe']) && $wahaMessage['fromMe'] === true) {
                        $skippedCount++;
                        continue;
                    }

                    $whatsappMessageId = $wahaMessage['id'] ?? null;
                    if (!$whatsappMessageId) {
                        $skippedCount++;
                        continue;
                    }

                    // Check if message already exists
                    $existingMessage = Message::where('whatsapp_message_id', $whatsappMessageId)
                        ->where('session_id', $session->id)
                        ->first();

                    if ($existingMessage) {
                        $skippedCount++;
                        continue;
                    }

                    // Extract phone numbers
                    $from = $wahaMessage['from'] ?? null;
                    $to = $wahaMessage['to'] ?? null;
                    $fromNumber = $this->extractPhoneNumber($from);
                    $toNumber = $this->extractPhoneNumber($to);

                    // Determine message type
                    $messageType = $this->determineMessageType($wahaMessage);
                    
                    // Extract content
                    $content = $wahaMessage['body'] ?? null;
                    $mediaUrl = null;
                    $mediaMimeType = null;
                    $mediaSize = null;
                    $caption = null;

                    if (!empty($wahaMessage['hasMedia']) && !empty($wahaMessage['media'])) {
                        $media = $wahaMessage['media'];
                        $mediaUrl = $media['url'] ?? null;
                        $mediaMimeType = $media['mimetype'] ?? null;
                        $mediaSize = $media['fileLength'] ?? $media['size'] ?? null;
                        
                        if (!empty($wahaMessage['body']) && $messageType !== 'text') {
                            $caption = $wahaMessage['body'];
                        }
                    }

                    // Handle special message types
                    if ($messageType === 'poll' && !empty($wahaMessage['poll'])) {
                        $content = json_encode($wahaMessage['poll']);
                    } elseif ($messageType === 'button' && !empty($wahaMessage['buttons'])) {
                        $content = json_encode([
                            'body' => $wahaMessage['body'] ?? '',
                            'buttons' => $wahaMessage['buttons'] ?? [],
                        ]);
                    } elseif ($messageType === 'list' && !empty($wahaMessage['list'])) {
                        $content = json_encode($wahaMessage['list']);
                    }

                    // Parse timestamp
                    $timestamp = $wahaMessage['timestamp'] ?? time();
                    if (is_float($timestamp)) {
                        $createdAt = \Carbon\Carbon::createFromTimestamp($timestamp);
                    } else {
                        $createdAt = \Carbon\Carbon::parse($timestamp);
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
                        'direction' => 'incoming',
                        'status' => 'delivered',
                        'sent_at' => $createdAt,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $syncedCount++;
                } catch (\Exception $e) {
                    \Log::error('API: Error syncing individual message', [
                        'error' => $e->getMessage(),
                        'message_id' => $wahaMessage['id'] ?? null,
                    ]);
                    $skippedCount++;
                }
            }

            \Log::info('API: Messages sync completed', [
                'session_id' => $session->session_id,
                'synced' => $syncedCount,
                'skipped' => $skippedCount,
                'total' => count($messages),
            ]);

            $this->usageService->log($request, 200, $startTime);
            return response()->json([
                'success' => true,
                'data' => [
                    'synced' => $syncedCount,
                    'skipped' => $skippedCount,
                    'total' => count($messages),
                ],
            ]);
        } catch (\Exception $e) {
            $this->usageService->log($request, 500, $startTime);
            \Log::error('API: Error syncing messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
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
}


