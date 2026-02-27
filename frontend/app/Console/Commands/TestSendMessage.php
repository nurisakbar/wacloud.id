<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppSession;
use App\Models\Message;
use App\Jobs\SendMessage as SendMessageJob;
use App\Helpers\PhoneNumberHelper;

class TestSendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-send {phone : The target phone number} 
                            {--session= : The WAHA session ID or database ID} 
                            {--message=Test Message from CLI : The message content}
                            {--document= : Optional PDF/Document URL to send}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test WhatsApp message using a specific session';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $phoneInput = $this->argument('phone');
        $sessionId = $this->option('session');
        $content = $this->option('message');

        if (!$sessionId) {
            $session = WhatsAppSession::where('status', 'connected')->first();
            if (!$session) {
                $this->error('No connected session found in database.');
                return 1;
            }
            $this->info("Using first available connected session: {$session->session_id} ({$session->phone_number})");
        } else {
            $session = WhatsAppSession::where('session_id', $sessionId)
                ->orWhere('id', $sessionId)
                ->first();
            
            if (!$session) {
                $this->error("Session not found: {$sessionId}");
                return 1;
            }
        }

        $normalizedNumberForDb = PhoneNumberHelper::normalize($phoneInput);
        if (!$normalizedNumberForDb) {
            $this->error("Invalid phone number format: {$phoneInput}");
            return 1;
        }

        $normalizedNumberForChatId = PhoneNumberHelper::normalizeForChatId($phoneInput);
        $chatId = $normalizedNumberForChatId . '@c.us';

        $this->info("Sending test message to {$normalizedNumberForDb}...");

        // Try to get phone number from device_info or session data
        $fromNumber = null;
        if ($session->phone_number) {
            $fromNumber = str_replace(['+', ' '], '', $session->phone_number);
        }
        if (!$fromNumber) {
            if ($session->device_info && isset($session->device_info['phone'])) {
                $fromNumber = $session->device_info['phone'];
            } elseif ($session->device_info && isset($session->device_info['wid'])) {
                $wid = $session->device_info['wid'];
                if (is_string($wid) && strpos($wid, '@') !== false) {
                    $fromNumber = explode('@', $wid)[0];
                }
            }
        }

        $documentUrl = $this->option('document');
        $messageType = $documentUrl ? 'document' : 'text';

        $message = Message::create([
            'user_id' => $session->user_id,
            'session_id' => $session->id,
            'from_number' => $fromNumber,
            'to_number' => $normalizedNumberForDb,
            'chat_type' => 'personal',
            'message_type' => $messageType,
            'content' => $messageType === 'text' ? $content . " (CLI Test)" : null,
            'caption' => $messageType === 'document' ? $content . " (CLI Test)" : null,
            'direction' => 'outgoing',
            'status' => 'pending',
        ]);

        SendMessageJob::dispatchSync(
            $message->id,
            $session->id,
            $chatId,
            $messageType,
            $message->content,
            null, // mediaPath
            null, // documentPath
            $documentUrl, // documentUrl
            null, // imageUrl
            null, // videoUrl
            $message->caption, // caption
            'personal'
        );

        $message->refresh();
        
        if ($message->status === 'sent' || $message->status === 'pending') {
            $this->info("Message dispatched successfully.");
            $this->info("Database Message ID: {$message->id}");
            $this->info("WhatsApp Message ID: {$message->whatsapp_message_id}");
            $this->info("Current Status: {$message->status}");
        } else {
            $this->error("Failed to send message. Check logs for details.");
            $this->error("Error: " . ($message->error_message ?: 'Unknown error'));
        }

        return 0;
    }
}
