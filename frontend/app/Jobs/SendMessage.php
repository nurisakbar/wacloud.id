<?php

namespace App\Jobs;

use App\Helpers\PhoneNumberHelper;
use App\Models\Message;
use App\Models\MessagePricingSetting;
use App\Models\QuotaUsageLog;
use App\Models\UserQuota;
use App\Models\WhatsAppSession;
use App\Services\WahaService;
use App\Services\WatermarkService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [10, 30, 60];

    protected $messageId;
    protected $sessionId;
    protected $chatId;
    protected $messageType;
    protected $content;
    protected $mediaPath;
    protected $documentPath;
    protected $documentUrl;
    protected $imageUrl;
    protected $videoUrl;
    protected $caption;
    protected $chatType;
    protected $pollName;
    protected $pollOptions;
    protected $multipleAnswers;
    protected $fallbackToText;
    protected $buttons;
    protected $header;
    protected $footer;
    protected $headerImage;
    protected $listMessage;
    protected $replyTo;
    protected $filename;
    protected $asNote;
    protected $convert;
    protected $quotaAlreadyDeducted;
    protected $quotaType;
    protected $quotaAmount;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $messageId,
        string $sessionId,
        string $chatId,
        string $messageType,
        ?string $content = null,
        ?string $mediaPath = null,
        ?string $documentPath = null,
        ?string $documentUrl = null,
        ?string $imageUrl = null,
        ?string $videoUrl = null,
        ?string $caption = null,
        string $chatType = 'personal',
        ?string $pollName = null,
        ?array $pollOptions = null,
        ?bool $multipleAnswers = null,
        ?bool $fallbackToText = null,
        ?array $buttons = null,
        ?string $header = null,
        ?string $footer = null,
        ?string $headerImage = null,
        ?array $listMessage = null,
        ?string $replyTo = null,
        ?string $filename = null,
        ?bool $asNote = null,
        ?bool $convert = null,
        bool $quotaAlreadyDeducted = false,
        ?string $quotaType = null,
        float $quotaAmount = 0
    ) {
        $this->messageId = $messageId;
        $this->sessionId = $sessionId;
        $this->chatId = $chatId;
        $this->messageType = $messageType;
        $this->content = $content;
        $this->mediaPath = $mediaPath;
        $this->documentPath = $documentPath;
        $this->documentUrl = $documentUrl;
        $this->imageUrl = $imageUrl;
        $this->videoUrl = $videoUrl;
        $this->caption = $caption;
        $this->chatType = $chatType;
        $this->pollName = $pollName;
        $this->pollOptions = $pollOptions;
        $this->multipleAnswers = $multipleAnswers;
        $this->fallbackToText = $fallbackToText;
        $this->buttons = $buttons;
        $this->header = $header;
        $this->footer = $footer;
        $this->headerImage = $headerImage;
        $this->listMessage = $listMessage;
        $this->replyTo = $replyTo;
        $this->filename = $filename;
        $this->asNote = $asNote;
        $this->convert = $convert;
        $this->quotaAlreadyDeducted = $quotaAlreadyDeducted;
        $this->quotaType = $quotaType;
        $this->quotaAmount = $quotaAmount;
    }

    /**
     * Execute the job.
     */
    public function handle(WahaService $wahaService, WatermarkService $watermarkService): void
    {
        $message = Message::find($this->messageId);
        if (!$message) {
            Log::error('SendMessage Job: Message not found', ['message_id' => $this->messageId]);
            return;
        }

        $session = WhatsAppSession::find($this->sessionId);
        if (!$session || $session->status !== 'connected') {
            Log::error('SendMessage Job: Device not found or not connected', [
                'session_id' => $this->sessionId,
                'message_id' => $this->messageId,
            ]);
            $message->update([
                'status' => 'failed',
                'error_message' => 'Device not found or not connected',
            ]);
            return;
        }

        // Get pricing settings
        $pricing = MessagePricingSetting::getActive();
        $userQuota = UserQuota::getOrCreateForUser($message->user_id);

        try {
            $result = null;
            $finalContent = $this->content;
            $price = 0;
            $quotaDeducted = $this->quotaAlreadyDeducted;
            $quotaType = $this->quotaType;
            $quotaAmount = $this->quotaAmount;

            switch ($this->messageType) {
                case 'text':
                    // If quota already deducted in controller, skip deduction
                    if (!$this->quotaAlreadyDeducted) {
                        // Determine if should use watermark (free) or premium
                        $watermarkPrice = $pricing->getPriceForMessageType('text', true);
                        $premiumPrice = $pricing->getPriceForMessageType('text', false);
                        
                        // PRIORITAS: 1. text_quota (non-watermark), 2. free_text_quota (watermark), 3. balance
                        
                        // Prioritas 1: Cek text_quota (premium, tanpa watermark) terlebih dahulu
                        if ($userQuota->text_quota > 0) {
                        // Use text quota first
                        if (!$userQuota->deductTextQuota(1)) {
                            throw new \Exception('Insufficient text quota');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'text_quota';
                        $quotaAmount = 1;
                        $price = 0; // Tidak ada biaya karena menggunakan quota
                        $withWatermark = false;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'text_quota',
                            'amount' => 1,
                            'message_type' => 'text',
                            'description' => 'Premium text message (without watermark)',
                        ]);
                        
                        Log::info('SendMessage Job: Text quota deducted', [
                            'message_id' => $this->messageId,
                            'remaining_quota' => $userQuota->fresh()->text_quota,
                        ]);
                        
                        // Tidak perlu watermark, gunakan content asli
                        $finalContent = $this->content;
                    }
                    // Prioritas 2: Jika text_quota habis, cek free_text_quota (dengan watermark)
                    elseif ($watermarkPrice == 0 && $userQuota->hasFreeTextQuota(1)) {
                        // Deduct free text quota
                        if (!$userQuota->deductFreeTextQuota(1)) {
                            throw new \Exception('Insufficient free text quota. Please wait until next month or purchase premium quota.');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'free_text_quota';
                        $quotaAmount = 1;
                        $price = 0;
                        $withWatermark = true;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'free_text_quota',
                            'amount' => 1,
                            'message_type' => 'text',
                            'description' => 'Free text message with watermark',
                        ]);
                        
                        Log::info('SendMessage Job: Free text quota deducted', [
                            'message_id' => $this->messageId,
                            'remaining_free_quota' => $userQuota->fresh()->free_text_quota,
                        ]);
                        
                        // Tambahkan watermark
                        $finalContent = $watermarkService->addWatermark($this->content, $pricing->watermark_text);
                        // Update message content in database
                        $message->update(['content' => $finalContent]);
                        
                        Log::info('SendMessage Job: Using free text message with watermark', [
                            'message_id' => $this->messageId,
                        ]);
                    }
                    // Prioritas 3: Jika keduanya habis, gunakan balance
                    elseif ($userQuota->hasEnoughBalance($premiumPrice)) {
                        // Use balance
                        if (!$userQuota->deductBalance($premiumPrice)) {
                            throw new \Exception('Insufficient balance');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $premiumPrice;
                        $price = $premiumPrice;
                        $withWatermark = false;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'balance',
                            'amount' => $premiumPrice,
                            'message_type' => 'text',
                            'description' => 'Premium text message paid with balance',
                        ]);
                        
                        Log::info('SendMessage Job: Balance deducted for premium text', [
                            'message_id' => $this->messageId,
                            'price' => $premiumPrice,
                            'remaining_balance' => $userQuota->fresh()->balance,
                        ]);
                        
                        // Tidak perlu watermark, gunakan content asli
                        $finalContent = $this->content;
                        } else {
                            // Semua quota habis
                            throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                        }
                    } else {
                        // Quota already deducted in controller, use content as is
                        // Content already has watermark if it was free_text_quota
                        $finalContent = $this->content;
                        
                        Log::info('SendMessage Job: Using pre-deducted quota', [
                            'message_id' => $this->messageId,
                            'quota_type' => $this->quotaType,
                            'content_length' => strlen($finalContent),
                        ]);
                    }

                    Log::info('SendMessage Job: Sending text message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'with_watermark' => $withWatermark,
                        'price' => $price,
                    ]);

                    $result = $wahaService->sendText(
                        $session->session_id,
                        $this->chatId,
                        $finalContent
                    );
                    break;

                case 'image':
                    // If quota already deducted in controller, skip deduction
                    if (!$this->quotaAlreadyDeducted) {
                        // Multimedia message - charge user
                        $price = $pricing->getPriceForMessageType('image');
                        
                        // Check quota BEFORE sending
                        if ($userQuota->multimedia_quota > 0) {
                        // Use multimedia quota first
                        if (!$userQuota->deductMultimediaQuota(1)) {
                            throw new \Exception('Insufficient multimedia quota');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'multimedia_quota';
                        $quotaAmount = 1;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'multimedia_quota',
                            'amount' => 1,
                            'message_type' => 'image',
                            'description' => 'Image message',
                        ]);
                        
                        Log::info('SendMessage Job: Multimedia quota deducted for image', [
                            'message_id' => $this->messageId,
                            'remaining_quota' => $userQuota->fresh()->multimedia_quota,
                        ]);
                    } elseif ($userQuota->hasEnoughBalance($price)) {
                        // Use balance
                        if (!$userQuota->deductBalance($price)) {
                            throw new \Exception('Insufficient balance');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $price;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'balance',
                            'amount' => $price,
                            'message_type' => 'image',
                            'description' => 'Image message paid with balance',
                        ]);
                        
                        Log::info('SendMessage Job: Balance deducted for image', [
                            'message_id' => $this->messageId,
                            'price' => $price,
                            'remaining_balance' => $userQuota->fresh()->balance,
                        ]);
                        } else {
                            throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                        }
                    }

                    Log::info('SendMessage Job: Sending image message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                    ]);

                    // Support both file path and URL
                    if ($this->imageUrl) {
                        $result = $wahaService->sendImageByUrl(
                            $session->session_id,
                            $this->chatId,
                            $this->imageUrl,
                            $this->caption
                        );
                    } else {
                        $fullPath = $this->mediaPath ? storage_path('app/public/' . $this->mediaPath) : null;
                        if (!$fullPath || !file_exists($fullPath)) {
                            throw new \Exception('Image file not found: ' . $this->mediaPath);
                        }

                        $result = $wahaService->sendImage(
                            $session->session_id,
                            $this->chatId,
                            $fullPath,
                            $this->caption
                        );
                    }
                    break;

                case 'video':
                    // If quota already deducted in controller, skip deduction
                    if (!$this->quotaAlreadyDeducted) {
                        // Multimedia message - charge user
                        $price = $pricing->getPriceForMessageType('video');
                        
                        // Check quota BEFORE sending
                        if ($userQuota->multimedia_quota > 0) {
                            // Use multimedia quota first
                            if (!$userQuota->deductMultimediaQuota(1)) {
                                throw new \Exception('Insufficient multimedia quota');
                            }
                            $quotaDeducted = true;
                            $quotaType = 'multimedia_quota';
                            $quotaAmount = 1;
                            
                            // Log quota usage
                            QuotaUsageLog::create([
                                'user_id' => $message->user_id,
                                'message_id' => $this->messageId,
                                'quota_type' => 'multimedia_quota',
                                'amount' => 1,
                                'message_type' => 'video',
                                'description' => 'Video message',
                            ]);
                            
                            Log::info('SendMessage Job: Multimedia quota deducted for video', [
                                'message_id' => $this->messageId,
                                'remaining_quota' => $userQuota->fresh()->multimedia_quota,
                            ]);
                        } elseif ($userQuota->hasEnoughBalance($price)) {
                            // Use balance
                            if (!$userQuota->deductBalance($price)) {
                                throw new \Exception('Insufficient balance');
                            }
                            $quotaDeducted = true;
                            $quotaType = 'balance';
                            $quotaAmount = $price;
                            
                            // Log quota usage
                            QuotaUsageLog::create([
                                'user_id' => $message->user_id,
                                'message_id' => $this->messageId,
                                'quota_type' => 'balance',
                                'amount' => $price,
                                'message_type' => 'video',
                                'description' => 'Video message paid with balance',
                            ]);
                            
                            Log::info('SendMessage Job: Balance deducted for video', [
                                'message_id' => $this->messageId,
                                'price' => $price,
                                'remaining_balance' => $userQuota->fresh()->balance,
                            ]);
                        } else {
                            throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                        }
                    }

                    Log::info('SendMessage Job: Sending video message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'video_url' => $this->videoUrl,
                    ]);

                    if (!$this->videoUrl) {
                        throw new \Exception('Video URL is required');
                    }

                    $result = $wahaService->sendVideoByUrl(
                        $session->session_id,
                        $this->chatId,
                        $this->videoUrl,
                        $this->caption,
                        $this->asNote ?? false,
                        $this->convert ?? false
                    );
                    break;

                case 'document':
                    // If quota already deducted in controller, skip deduction
                    if (!$this->quotaAlreadyDeducted) {
                        // Multimedia message - charge user
                        $price = $pricing->getPriceForMessageType('document');
                        
                        // Check quota BEFORE sending
                        if ($userQuota->multimedia_quota > 0) {
                        // Use multimedia quota first
                        if (!$userQuota->deductMultimediaQuota(1)) {
                            throw new \Exception('Insufficient multimedia quota');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'multimedia_quota';
                        $quotaAmount = 1;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'multimedia_quota',
                            'amount' => 1,
                            'message_type' => 'document',
                            'description' => 'Document message',
                        ]);
                        
                        Log::info('SendMessage Job: Multimedia quota deducted for document', [
                            'message_id' => $this->messageId,
                            'remaining_quota' => $userQuota->fresh()->multimedia_quota,
                        ]);
                    } elseif ($userQuota->hasEnoughBalance($price)) {
                        // Use balance
                        if (!$userQuota->deductBalance($price)) {
                            throw new \Exception('Insufficient balance');
                        }
                        $quotaDeducted = true;
                        $quotaType = 'balance';
                        $quotaAmount = $price;
                        
                        // Log quota usage
                        QuotaUsageLog::create([
                            'user_id' => $message->user_id,
                            'message_id' => $this->messageId,
                            'quota_type' => 'balance',
                            'amount' => $price,
                            'message_type' => 'document',
                            'description' => 'Document message paid with balance',
                        ]);
                        
                        Log::info('SendMessage Job: Balance deducted for document', [
                            'message_id' => $this->messageId,
                            'price' => $price,
                            'remaining_balance' => $userQuota->fresh()->balance,
                        ]);
                        } else {
                            throw new \Exception('Insufficient quota or balance. Please purchase quota first.');
                        }
                    }

                    Log::info('SendMessage Job: Sending document message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'has_file' => !empty($this->documentPath),
                        'has_url' => !empty($this->documentUrl),
                    ]);

                    if ($this->documentPath) {
                        $fullPath = storage_path('app/public/' . $this->documentPath);
                        if (!file_exists($fullPath)) {
                            throw new \Exception('Document file not found: ' . $this->documentPath);
                        }
                        $fileName = basename($fullPath);
                        $result = $wahaService->sendDocument(
                            $session->session_id,
                            $this->chatId,
                            $fullPath,
                            $fileName
                        );
                    } elseif ($this->documentUrl) {
                        $result = $wahaService->sendDocumentByUrl(
                            $session->session_id,
                            $this->chatId,
                            $this->documentUrl,
                            $this->filename
                        );
                    } else {
                        throw new \Exception('No document file or URL provided');
                    }
                    break;

                case 'poll':
                    // Poll messages don't charge quota (free)
                    Log::info('SendMessage Job: Sending poll message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'poll_name' => $this->pollName,
                        'options_count' => count($this->pollOptions ?? []),
                    ]);

                    if (!$this->pollName || empty($this->pollOptions)) {
                        throw new \Exception('Poll name and options are required');
                    }

                    $result = $wahaService->sendPoll(
                        $session->session_id,
                        $this->chatId,
                        $this->pollName,
                        $this->pollOptions,
                        $this->multipleAnswers ?? false,
                        $this->fallbackToText ?? false
                    );

                    // Handle fallback to text if used
                    if ($result['success'] && isset($result['fallback_used']) && $result['fallback_used']) {
                        $textContent = '';
                        if (isset($result['data']['_data']['Message']['extendedTextMessage']['text'])) {
                            $textContent = $result['data']['_data']['Message']['extendedTextMessage']['text'];
                        } elseif (isset($result['data']['body'])) {
                            $textContent = $result['data']['body'];
                        }
                        $message->update([
                            'content' => $textContent,
                            'message_type' => 'text',
                        ]);
                    }
                    break;

                case 'button':
                    // Button messages don't charge quota (free)
                    Log::info('SendMessage Job: Sending button message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'body_length' => strlen($this->content ?? ''),
                        'buttons_count' => count($this->buttons ?? []),
                    ]);

                    if (!$this->content || empty($this->buttons)) {
                        throw new \Exception('Body and buttons are required for button message');
                    }

                    $result = $wahaService->sendButton(
                        $session->session_id,
                        $this->chatId,
                        $this->content,
                        $this->buttons,
                        $this->header,
                        $this->footer,
                        $this->headerImage,
                        $this->fallbackToText ?? false
                    );

                    // Handle fallback to text if used
                    if ($result['success'] && isset($result['fallback_used']) && $result['fallback_used']) {
                        $textContent = '';
                        if (isset($result['data']['_data']['Message']['extendedTextMessage']['text'])) {
                            $textContent = $result['data']['_data']['Message']['extendedTextMessage']['text'];
                        } elseif (isset($result['data']['body'])) {
                            $textContent = $result['data']['body'];
                        }
                        $message->update([
                            'content' => $textContent,
                            'message_type' => 'text',
                        ]);
                    }
                    break;

                case 'list':
                    // List messages don't charge quota (free)
                    Log::info('SendMessage Job: Sending list message', [
                        'session_id' => $session->session_id,
                        'chat_id' => $this->chatId,
                        'message_id' => $this->messageId,
                        'message_title' => $this->listMessage['title'] ?? null,
                        'sections_count' => count($this->listMessage['sections'] ?? []),
                    ]);

                    if (!$this->listMessage) {
                        throw new \Exception('List message data is required');
                    }

                    $result = $wahaService->sendList(
                        $session->session_id,
                        $this->chatId,
                        $this->listMessage,
                        $this->replyTo
                    );
                    break;
            }

            if ($result && $result['success']) {
                // Extract WhatsApp message ID - try multiple possible locations
                $whatsappId = $result['data']['id'] ?? 
                             $result['data']['key']['id'] ?? 
                             $result['data']['_data']['Info']['ID'] ?? 
                             $result['data']['messageId'] ?? 
                             null;
                
                if (is_array($whatsappId)) {
                    $whatsappId = json_encode($whatsappId);
                }
                
                // Check ack status from WAHA response
                $statusFromResponse = $result['data']['status'] ?? null;
                $ack = $result['data']['ack'] ?? 
                       $result['data']['_data']['ack'] ?? 
                       $result['data']['_data']['Info']['ack'] ?? 
                       null;
                $status = 'sent';
                
                // ack: 0 = pending, 1 = delivered, 2 = read, 3 = played
                if ($ack === 0 || $statusFromResponse === 'PENDING') {
                    $status = 'pending';
                }

                $message->update([
                    'whatsapp_message_id' => $whatsappId,
                    'status' => $status,
                    'sent_at' => now(),
                ]);

                Log::info('SendMessage Job: Message sent successfully', [
                    'message_id' => $this->messageId,
                    'whatsapp_message_id' => $whatsappId,
                    'status' => $status,
                    'quota_deducted' => $quotaDeducted,
                    'quota_type' => $quotaType,
                ]);
            } else {
                $errorMessage = $result['error'] ?? 'Failed to send message';
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }

                // If quota was deducted but message failed, refund it and delete usage log
                if ($quotaDeducted) {
                    $userQuota = UserQuota::getOrCreateForUser($message->user_id);
                    if ($quotaType === 'free_text_quota') {
                        $userQuota->addFreeTextQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'free_text_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded free text quota due to send failure', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'text_quota') {
                        $userQuota->addTextQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'text_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded text quota due to send failure', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'multimedia_quota') {
                        $userQuota->addMultimediaQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'multimedia_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded multimedia quota due to send failure', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'balance') {
                        $userQuota->addBalance($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'balance')
                            ->delete();
                        Log::info('SendMessage Job: Refunded balance due to send failure', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    }
                }

                $message->update([
                    'status' => 'failed',
                    'error_message' => $errorMessage,
                ]);

                Log::error('SendMessage Job: Failed to send message', [
                    'message_id' => $this->messageId,
                    'error' => $errorMessage,
                ]);

                throw new \Exception($errorMessage);
            }
        } catch (\Exception $e) {
            // If quota was deducted but exception occurred, refund it and delete usage log
            if (isset($quotaDeducted) && $quotaDeducted && isset($quotaType) && isset($quotaAmount)) {
                try {
                    $userQuota = UserQuota::getOrCreateForUser($message->user_id);
                    if ($quotaType === 'free_text_quota') {
                        $userQuota->addFreeTextQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'free_text_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded free text quota due to exception', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'text_quota') {
                        $userQuota->addTextQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'text_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded text quota due to exception', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'multimedia_quota') {
                        $userQuota->addMultimediaQuota($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'multimedia_quota')
                            ->delete();
                        Log::info('SendMessage Job: Refunded multimedia quota due to exception', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    } elseif ($quotaType === 'balance') {
                        $userQuota->addBalance($quotaAmount);
                        // Delete usage log
                        QuotaUsageLog::where('message_id', $this->messageId)
                            ->where('quota_type', 'balance')
                            ->delete();
                        Log::info('SendMessage Job: Refunded balance due to exception', [
                            'message_id' => $this->messageId,
                            'amount' => $quotaAmount,
                        ]);
                    }
                } catch (\Exception $refundException) {
                    Log::error('SendMessage Job: Failed to refund quota', [
                        'message_id' => $this->messageId,
                        'error' => $refundException->getMessage(),
                    ]);
                }
            }

            Log::error('SendMessage Job: Exception occurred', [
                'message_id' => $this->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $message = Message::find($this->messageId);
        if ($message) {
            $message->update([
                'status' => 'failed',
                'error_message' => 'Job failed after ' . $this->tries . ' attempts: ' . $exception->getMessage(),
            ]);
        }

        Log::error('SendMessage Job: Job failed permanently', [
            'message_id' => $this->messageId,
            'error' => $exception->getMessage(),
        ]);
    }
}
