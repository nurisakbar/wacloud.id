<?php

namespace App\Http\Controllers;

use App\Helpers\DebugLog;
use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendMessage as SendMessageJob;
use App\Models\Message;
use App\Models\WhatsAppSession;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;

class MessageController extends Controller
{
    protected WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->middleware('auth');
        $this->wahaService = $wahaService;
    }

    /**
     * Display a listing of messages.
     */
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            \Log::info('DataTables request received', [
                'is_ajax' => $request->ajax(),
                'wants_json' => $request->wantsJson(),
                'headers' => $request->headers->all(),
            ]);
            return $this->datatables($request);
        }

        $sessions = Auth::user()->whatsappSessions()->where('status', 'connected')->get();
        $autoSyncEnabled = Auth::user()->auto_sync_incoming_messages ?? true;
        
        return view('messages.index', compact('sessions', 'autoSyncEnabled'));
    }

    /**
     * Toggle auto sync incoming messages setting
     */
    public function toggleAutoSync(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        Auth::user()->update([
            'auto_sync_incoming_messages' => $request->enabled,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->enabled ? 'Auto sync pesan masuk diaktifkan' : 'Auto sync pesan masuk dinonaktifkan',
        ]);
    }

    /**
     * Sync incoming messages from WAHA API
     */
    public function syncIncoming(Request $request)
    {
        try {
            $user = Auth::user();
            $sessions = $user->whatsappSessions()->where('status', 'connected')->get();
            
            if ($sessions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada device yang terhubung',
                ], 400);
            }

            $totalSynced = 0;
            $totalSkipped = 0;
            $errors = [];

            foreach ($sessions as $session) {
                try {
                    \Log::info('Syncing incoming messages for session', [
                        'session_id' => $session->session_id,
                        'user_id' => $user->id,
                    ]);

                    // Strategy: Try multiple methods to get messages
                    // 1. Try to get contacts first (usually works without store)
                    // 2. Use contacts to get chatIds and fetch messages
                    // 3. Fallback to chats if available
                    
                    $allMessages = [];
                    $contacts = [];
                    
                    // Method 1: Try to get contacts
                    \Log::info('Trying to get contacts for session', [
                        'session_id' => $session->session_id,
                    ]);
                    
                    $contactsResult = $this->wahaService->getContacts($session->session_id);
                    if ($contactsResult['success'] && isset($contactsResult['data']) && is_array($contactsResult['data'])) {
                        $contacts = $contactsResult['data'];
                        \Log::info('Found contacts for session', [
                            'session_id' => $session->session_id,
                            'contacts_count' => count($contacts),
                        ]);
                    }
                    
                    // Method 2: Try to get chats (might require store)
                    $chats = [];
                    if (empty($contacts)) {
                        \Log::info('No contacts found, trying to get chats', [
                            'session_id' => $session->session_id,
                        ]);
                        
                        $chatsResult = $this->wahaService->getChats($session->session_id);
                        if ($chatsResult['success'] && isset($chatsResult['data']) && is_array($chatsResult['data'])) {
                            $chats = $chatsResult['data'];
                            \Log::info('Found chats for session', [
                                'session_id' => $session->session_id,
                                'chats_count' => count($chats),
                            ]);
                        } else {
                            $errorMsg = $chatsResult['error'] ?? 'Unknown error';
                            \Log::warning('Failed to get chats', [
                                'session_id' => $session->session_id,
                                'error' => $errorMsg,
                            ]);
                        }
                    }
                    
                    // Get messages from contacts or chats
                    $sources = !empty($contacts) ? $contacts : $chats;
                    
                    if (empty($sources)) {
                        // No contacts or chats available
                        $errors[] = "Session {$session->session_name}: Tidak dapat mendapatkan daftar kontak atau chat. Pastikan device sudah terhubung dan memiliki kontak/chat.";
                        continue;
                    }
                    
                    // Get messages from each contact/chat
                    foreach ($sources as $source) {
                        $chatId = $source['id'] ?? null;
                        if (!$chatId) {
                            continue;
                        }
                        
                        try {
                            $chatResult = $this->wahaService->getMessages($session->session_id, $chatId, 20);
                            
                            if ($chatResult['success'] && isset($chatResult['data']) && is_array($chatResult['data'])) {
                                $chatMessages = $chatResult['data'];
                                foreach ($chatMessages as $msg) {
                                    $allMessages[] = $msg;
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Error getting messages for chat', [
                                'chat_id' => $chatId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    if (empty($allMessages)) {
                        \Log::info('No messages found from contacts/chats', [
                            'session_id' => $session->session_id,
                        ]);
                        $errors[] = "Session {$session->session_name}: Tidak ada pesan yang ditemukan dari kontak/chat yang tersedia.";
                        continue;
                    }
                    
                    $messages = $allMessages;
                    \Log::info('Successfully got messages from contacts/chats', [
                        'session_id' => $session->session_id,
                        'messages_count' => count($messages),
                    ]);
                    
                    foreach ($messages as $wahaMessage) {
                        try {
                            // Skip outgoing messages (only sync incoming)
                            if (!empty($wahaMessage['fromMe']) && $wahaMessage['fromMe'] === true) {
                                $totalSkipped++;
                                continue;
                            }

                            $whatsappMessageId = $wahaMessage['id'] ?? null;
                            if (!$whatsappMessageId) {
                                $totalSkipped++;
                                continue;
                            }

                            // Check if message already exists
                            $existingMessage = Message::where('whatsapp_message_id', $whatsappMessageId)
                                ->where('session_id', $session->id)
                                ->first();

                            if ($existingMessage) {
                                $totalSkipped++;
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
                                $mediaSize = $media['size'] ?? null;
                                $caption = $wahaMessage['caption'] ?? null;
                                
                                // Convert relative URL to absolute
                                if ($mediaUrl && !filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
                                    $mediaUrl = rtrim($this->wahaService->getBaseUrl(), '/') . '/' . ltrim($mediaUrl, '/');
                                }
                            }

                            // Determine chat type
                            $chatType = 'personal';
                            if (str_contains($from ?? '', '@g.us') || str_contains($to ?? '', '@g.us')) {
                                $chatType = 'group';
                            }

                            // Get timestamp
                            $timestamp = $wahaMessage['timestamp'] ?? time();
                            $createdAt = \Carbon\Carbon::createFromTimestamp($timestamp);

                            // Create message record
                            Message::create([
                                'user_id' => $user->id,
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
                                'chat_type' => $chatType,
                                'direction' => 'incoming',
                                'status' => 'delivered',
                                'sent_at' => $createdAt,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt,
                            ]);

                            $totalSynced++;
                        } catch (\Exception $e) {
                            \Log::error('Error syncing individual message', [
                                'error' => $e->getMessage(),
                                'message_id' => $wahaMessage['id'] ?? null,
                            ]);
                            $totalSkipped++;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error syncing messages for session', [
                        'session_id' => $session->session_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $errorMsg = $e->getMessage();
                    if (is_array($errorMsg)) {
                        $errorMsg = json_encode($errorMsg);
                    }
                    $errors[] = "Session {$session->session_name}: " . $errorMsg;
                }
            }

            $message = "Sync selesai. {$totalSynced} pesan baru, {$totalSkipped} dilewati.";
            
            // Add warning if no messages synced and there are errors about store
            if ($totalSynced === 0 && !empty($errors)) {
                $hasStoreError = false;
                foreach ($errors as $error) {
                    if (str_contains(strtolower($error), 'store') || str_contains(strtolower($error), 'webhook')) {
                        $hasStoreError = true;
                        break;
                    }
                }
                
                if ($hasStoreError) {
                    $message .= " Catatan: Sync manual memerlukan konfigurasi store di WAHA. Pesan masuk akan otomatis tersimpan melalui webhook real-time saat ada pesan baru.";
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'synced' => $totalSynced,
                    'skipped' => $totalSkipped,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing incoming messages', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update status of pending messages
     * This method checks messages that are older than 5 seconds and updates their status
     * Since WAHA doesn't provide direct status check, we update based on time elapsed
     */
    public function updatePendingStatus(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get pending outgoing messages that are older than 5 seconds
            // Messages that are older than 5 seconds are likely already sent
            $pendingMessages = $user->messages()
                ->where('direction', 'outgoing')
                ->where('status', 'pending')
                ->whereNotNull('whatsapp_message_id')
                ->where('created_at', '<', now()->subSeconds(5))
                ->with('session')
                ->limit(100)
                ->get();

            if ($pendingMessages->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada pesan pending untuk diupdate',
                    'data' => [
                        'updated' => 0,
                    ],
                ]);
            }

            $updated = 0;
            $errors = [];

            foreach ($pendingMessages as $message) {
                try {
                    if (!$message->session || $message->session->status !== 'connected') {
                        continue;
                    }

                    // Try to get message from WAHA to verify it exists
                    $chatId = ($message->to_number ?? '') . '@c.us';
                    if ($message->chat_type === 'group') {
                        $chatId = ($message->to_number ?? '') . '@g.us';
                    }

                    // Get recent messages from WAHA to check if our message is there
                    $result = $this->wahaService->getMessages($message->session->session_id, $chatId, 20);
                    
                    if ($result['success'] && isset($result['data'])) {
                        $wahaMessages = $result['data'];
                        $found = false;
                        
                        // Check if our message exists in WAHA messages
                        foreach ($wahaMessages as $wahaMessage) {
                            if (isset($wahaMessage['id']) && $wahaMessage['id'] === $message->whatsapp_message_id) {
                                // Message found in WAHA, it was sent successfully
                                // Check if it's fromMe (outgoing)
                                if (!empty($wahaMessage['fromMe']) && $wahaMessage['fromMe'] === true) {
                                    $message->update([
                                        'status' => 'sent',
                                        'updated_at' => now(),
                                    ]);
                                    $updated++;
                                    $found = true;
                                    break;
                                }
                            }
                        }
                        
                        // Jika pesan tidak ditemukan dan usia sudah lebih dari 45 detik,
                        // kemungkinan gagal (WAHA menghapus dari antrean atau error engine).
                        if (!$found && $message->created_at < now()->subSeconds(45)) {
                            $message->update([
                                'status' => 'failed',
                                'error_message' => 'Pesan tidak ditemukan di histori WAHA setelah 45 detik.',
                                'updated_at' => now(),
                            ]);
                            $updated++;
                        }
                    } else {
                        // Jika tidak bisa fetch getMessages dan pesan sudah terlalu lama, biarkan saja
                        // atau mark as failed agar user tahu ada masalah koneksi ke WAHA.
                        if ($message->created_at < now()->subSeconds(60)) {
                            $message->update([
                                'status' => 'failed',
                                'error_message' => 'Gagal memeriksa status ke WAHA (API error).',
                                'updated_at' => now(),
                            ]);
                            $updated++;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Error updating message status', [
                        'message_id' => $message->id,
                        'error' => $e->getMessage(),
                    ]);
                    $errors[] = "Message {$message->id}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Update status selesai. {$updated} pesan diupdate.",
                'data' => [
                    'updated' => $updated,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating pending message status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract phone number from WAHA format
     */
    protected function extractPhoneNumber(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        // Remove @c.us or @g.us suffix
        $number = preg_replace('/@[cg]\.us$/', '', $value);
        
        // Remove + prefix if exists
        $number = ltrim($number, '+');
        
        return $number ?: null;
    }

    /**
     * Determine message type from WAHA payload
     */
    protected function determineMessageType(array $payload): string
    {
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
        
        if (!empty($payload['voice'])) {
            return 'voice';
        }
        
        if (!empty($payload['video'])) {
            return 'video';
        }
        
        if (!empty($payload['image'])) {
            return 'image';
        }
        
        if (!empty($payload['document'])) {
            return 'document';
        }
        
        return 'text';
    }

    /**
     * DataTables server-side processing.
     */
    public function datatables(Request $request)
    {
        try {
            $query = Auth::user()->messages()->with('session');

            // Filter by session
            if ($request->has('session_id') && $request->session_id) {
                $query->where('session_id', $request->session_id);
            }

            // Filter by direction
            if ($request->has('direction') && $request->direction) {
                $query->where('direction', $request->direction);
            }

            // Custom search filter (from filter form - separate from DataTables search)
            // DataTables sends search as object with 'value' property, but our custom filter sends as string
            if ($request->has('search') && is_string($request->search) && !empty($request->search)) {
                $searchValue = $request->search;
                $query->where(function($q) use ($searchValue) {
                    $q->where('content', 'like', '%' . $searchValue . '%')
                      ->orWhere('from_number', 'like', '%' . $searchValue . '%')
                      ->orWhere('to_number', 'like', '%' . $searchValue . '%');
                });
            }

            return DataTables::of($query)
            ->addColumn('content_preview', function ($message) {
                $preview = '';
                
                // Add content preview
                if ($message->message_type === 'text') {
                    $content = $message->content ?? __('No content');
                    $preview .= '<span class="message-preview">' . \Illuminate\Support\Str::limit(strip_tags($content), 100) . '</span>';
                } elseif ($message->message_type === 'image') {
                    $preview .= '<span class="text-muted">' . __('Image') . '</span>';
                    if ($message->caption) {
                        $preview .= '<br><small class="text-muted">' . \Illuminate\Support\Str::limit($message->caption, 50) . '</small>';
                    }
                } elseif ($message->message_type === 'video') {
                    $preview .= '<span class="text-muted">' . __('Video') . '</span>';
                    if ($message->caption) {
                        $preview .= '<br><small class="text-muted">' . \Illuminate\Support\Str::limit($message->caption, 50) . '</small>';
                    }
                } elseif ($message->message_type === 'document') {
                    $preview .= '<span class="text-muted">' . __('Document') . '</span>';
                    if ($message->caption) {
                        $preview .= '<br><small class="text-muted">' . \Illuminate\Support\Str::limit($message->caption, 50) . '</small>';
                    }
                } else {
                    $preview .= '<span class="text-muted">' . ucfirst($message->message_type) . '</span>';
                }
                
                return $preview;
            })
            ->addColumn('status_badge', function ($message) {
                $status = $message->status;
                $badgeClass = 'bg-secondary';
                $textColor = 'text-white';
                $label = ucfirst($status);
                
                switch ($status) {
                    case 'sent':
                        $badgeClass = 'bg-success';
                        $textColor = 'text-white';
                        $label = 'Sent';
                        break;
                    case 'delivered':
                        $badgeClass = 'bg-info';
                        $textColor = 'text-white';
                        $label = 'Delivered';
                        break;
                    case 'read':
                        $badgeClass = 'bg-primary';
                        $textColor = 'text-white';
                        $label = 'Read';
                        break;
                    case 'failed':
                        $badgeClass = 'bg-danger';
                        $textColor = 'text-white';
                        $label = 'Failed';
                        break;
                    case 'pending':
                        $badgeClass = 'bg-warning';
                        $textColor = 'text-dark';
                        $label = 'Pending';
                        break;
                }
                
                $statusBadge = '<span class="badge ' . $badgeClass . ' ' . $textColor . '" style="font-weight: 600;">' . $label . '</span>';
                
                // Add direction indicator below status
                $directionBadge = '';
                if ($message->isIncoming()) {
                    $directionBadge = '<br><span class="badge bg-primary text-white mt-1" style="font-size: 0.75em; font-weight: 500;"><i class="fas fa-inbox"></i> Incoming</span>';
                } else {
                    $directionBadge = '<br><span class="badge bg-success text-white mt-1" style="font-size: 0.75em; font-weight: 500;"><i class="fas fa-paper-plane"></i> Outgoing</span>';
                }
                
                return '<div>' . $statusBadge . $directionBadge . '</div>';
            })
            ->addColumn('formatted_date', function ($message) {
                $date = $message->created_at;
                return '<div class="text-nowrap">
                    <div class="fw-bold">' . $date->format('d/m/Y') . '</div>
                    <small class="text-muted">' . $date->format('H:i:s') . '</small>
                </div>';
            })
            ->addColumn('actions', function ($message) {
                return '<a href="' . route('messages.show', $message) . '" class="btn btn-sm btn-info" title="' . __('View Details') . '">
                    <i class="fas fa-eye"></i>
                </a>';
            })
            ->rawColumns(['content_preview', 'status_badge', 'formatted_date', 'actions'])
            ->make(true);
        } catch (\Exception $e) {
            \Log::error('DataTables error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for sending a new message.
     */
    public function create(Request $request)
    {
        $sessionId = $request->get('session_id');
        $toNumber = $request->get('to_number');
        $sessions = Auth::user()->whatsappSessions()->where('status', 'connected')->get();

        if ($sessions->isEmpty()) {
            return redirect()->route('sessions.index')
                ->with('error', 'You need at least one connected session to send messages.');
        }

        $selectedSession = $sessionId ? $sessions->find($sessionId) : $sessions->first();

        return view('messages.create', compact('sessions', 'selectedSession', 'toNumber'));
    }

    /**
     * Store a newly sent message.
     */
    public function store(Request $request)
    {
        $rules = [
            'session_id'    => 'required|exists:whatsapp_sessions,id',
            'to_number'     => 'required|string|max:255',
            'chat_type'     => 'nullable|in:personal,group',
            'message_type'  => 'required|in:text,image,document',
            'content'       => 'nullable|string|max:4096|required_if:message_type,text',
            'media'         => 'required_if:message_type,image|file|max:10240',
            'caption'       => 'nullable|string|max:1024',
        ];

        // For document type, require either file or URL (but not both empty)
        if ($request->message_type === 'document') {
            $rules['document_file'] = 'required_without:document_url|file|max:10240';
            $rules['document_url'] = 'required_without:document_file|nullable|url|max:2048';
        }

        // Determine chat type (personal or group)
        $chatType = $request->input('chat_type', 'personal'); // Default to personal
        
        // Add conditional validation for personal chat phone number
        if ($chatType === 'personal') {
            $request->validate(array_merge($rules, [
                'to_number' => [
                    'required',
                    'string',
                    'regex:/^08[0-9]{8,11}$/',
                ],
            ]), [
                'to_number.regex' => 'Format nomor telepon tidak valid. Gunakan format: 081395777706 (10-13 digit, dimulai dengan 08).',
            ]);
        } else {
            $request->validate($rules);
        }

        // Handle chat ID based on type
        if ($chatType === 'group') {
            // For group, the 'to_number' field should be the group ID
            $toValue = $request->to_number;
            
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
            // Convert 081395777706 -> +6281395777706 (for database)
            $normalizedNumberForDb = PhoneNumberHelper::normalize($request->to_number);
            if (!$normalizedNumberForDb) {
                return back()->withErrors(['to_number' => 'Format nomor telepon tidak valid. Gunakan format: 081395777706']);
            }
            
            // Convert 081395777706 -> 6281395777706 (for WAHA chatId, without +)
            // WAHA requires chatId format: 6281395777706@c.us (without +)
            $normalizedNumberForChatId = PhoneNumberHelper::normalizeForChatId($request->to_number);
            $chatId = $normalizedNumberForChatId . '@c.us';
        }

        $session = WhatsAppSession::where('user_id', Auth::id())
            ->where('id', $request->session_id)
            ->where('status', 'connected')
            ->firstOrFail();
        $result = null;
        
        // Try to get phone number from device_info or session data
        $fromNumber = null;
        if ($session->device_info && isset($session->device_info['phone'])) {
            $fromNumber = $session->device_info['phone'];
        } elseif ($session->device_info && isset($session->device_info['wid'])) {
            // Extract phone from wid (format: 6281234567890@c.us)
            $wid = $session->device_info['wid'];
            if (is_string($wid) && strpos($wid, '@') !== false) {
                $fromNumber = explode('@', $wid)[0];
            }
        }
        
        $messageData = [
            'user_id' => Auth::id(),
            'session_id' => $session->id,
            'from_number' => $fromNumber,
            'to_number' => $chatType === 'personal' ? $normalizedNumberForDb : $normalizedNumber,
            'chat_type' => $chatType,
            'message_type' => $request->message_type,
            'direction' => 'outgoing',
            'status' => 'pending',
        ];

        try {
            $mediaPath = null;
            $documentPath = null;
            $documentUrl = null;

            switch ($request->message_type) {
                case 'text':
                    $messageData['content'] = $request->content;
                    break;

                case 'image':
                    $file = $request->file('media');
                    $path = $file->store('messages/media', 'public');
                    $messageData['media_url'] = Storage::url($path);
                    $messageData['media_mime_type'] = $file->getMimeType();
                    $messageData['media_size'] = $file->getSize();
                    $messageData['caption'] = $request->caption;
                    $mediaPath = $path;
                    break;

                case 'document':
                    if ($request->hasFile('document_file')) {
                        $file = $request->file('document_file');
                        $path = $file->store('messages/documents', 'public');
                        $messageData['media_url'] = Storage::url($path);
                        $messageData['media_mime_type'] = $file->getMimeType();
                        $messageData['media_size'] = $file->getSize();
                        $documentPath = $path;
                    } elseif ($request->document_url) {
                        $messageData['media_url'] = $request->document_url;
                        $documentUrl = $request->document_url;
                    } else {
                        return back()->withErrors(['error' => 'Please provide either a document file or document URL.']);
                    }
                    $messageData['caption'] = $request->caption;
                    break;
            }

            // Create message record first
            $message = Message::create($messageData);

            // Dispatch job to send message asynchronously
            SendMessageJob::dispatch(
                $message->id,
                $session->id,
                $chatId,
                $request->message_type,
                $request->content,
                $mediaPath,
                $documentPath,
                $documentUrl,
                $request->caption,
                $chatType
            );

            DebugLog::log('[MessageController] store(): Sending message', [
                'message_id'   => $message->id,
                'session_id'   => $session->session_id,
                'chat_id'      => $chatId,
                'chat_type'    => $chatType,
                'message_type' => $request->message_type,
                'to_number'    => $request->to_number,
                'content'      => $request->message_type === 'text' ? $request->content : '[media]',
                'user_id'      => Auth::id(),
            ]);

            \Log::info('Browser: Message job dispatched', [
                'message_id' => $message->id,
                'session_id' => $session->session_id,
                'chat_id' => $chatId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('messages.index')
                ->with('success', 'Message queued for sending!');
        } catch (\Exception $e) {
            $messageData['status'] = 'failed';
            $errorMessage = $e->getMessage();
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            $messageData['error_message'] = $errorMessage;
            Message::create($messageData);

            return back()->withErrors(['error' => 'Error: ' . $errorMessage]);
        }
    }

    /**
     * Store bulk messages from Excel file.
     */
    public function storeBulk(Request $request)
    {
        $rules = [
            'session_id' => 'required|exists:whatsapp_sessions,id',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
            'delay_enabled' => 'nullable|boolean',
            'delay_seconds' => 'nullable|integer|min:1|max:60',
        ];

        $request->validate($rules);

        $session = WhatsAppSession::where('user_id', Auth::id())
            ->where('id', $request->session_id)
            ->where('status', 'connected')
            ->firstOrFail();

        $delayEnabled = $request->has('delay_enabled') && $request->delay_enabled;
        $delaySeconds = $delayEnabled ? (int)($request->delay_seconds ?? 2) : 0;

        DebugLog::log('[MessageController] storeBulk(): Bulk message request', [
            'session_id'    => $session->session_id,
            'user_id'       => Auth::id(),
            'delay_enabled' => $delayEnabled ?? false,
            'delay_seconds' => $delaySeconds ?? 0,
            'file_name'     => $request->file('excel_file')?->getClientOriginalName(),
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                return back()->withErrors(['excel_file' => 'Excel file must have at least one data row (excluding header).']);
            }

            // Get header row (first row)
            $headerRow = array_shift($rows);
            $headerRow = array_map('strtolower', array_map('trim', $headerRow));

            // Find column indices
            $phoneColumnIndex = null;
            $messageColumnIndex = null;

            foreach ($headerRow as $index => $header) {
                if (in_array($header, ['nomor', 'phone', 'phone_number', 'number', 'no', 'telepon'])) {
                    $phoneColumnIndex = $index;
                }
                if (in_array($header, ['pesan', 'message', 'text', 'content', 'isi'])) {
                    $messageColumnIndex = $index;
                }
            }

            if ($phoneColumnIndex === null || $messageColumnIndex === null) {
                return back()->withErrors([
                    'excel_file' => 'Excel file must have columns: "nomor" (or phone/number) and "pesan" (or message/text/content).'
                ]);
            }

            $results = [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];

            // Try to get phone number from device_info
            $fromNumber = null;
            if ($session->device_info && isset($session->device_info['phone'])) {
                $fromNumber = $session->device_info['phone'];
            } elseif ($session->device_info && isset($session->device_info['wid'])) {
                $wid = $session->device_info['wid'];
                if (is_string($wid) && strpos($wid, '@') !== false) {
                    $fromNumber = explode('@', $wid)[0];
                }
            }

            // Process each row
            foreach ($rows as $rowIndex => $row) {
                $results['total']++;

                // Skip empty rows
                if (empty($row[$phoneColumnIndex]) || empty($row[$messageColumnIndex])) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($rowIndex + 2) . ": Missing phone number or message";
                    continue;
                }

                $phoneNumber = trim($row[$phoneColumnIndex]);
                $messageContent = trim($row[$messageColumnIndex]);

                // Validate phone number format (must be 08xxxxxxxxxx)
                if (!preg_match('/^08[0-9]{8,11}$/', $phoneNumber)) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($rowIndex + 2) . ": Invalid phone number format: " . $phoneNumber . " (Use format: 081395777706)";
                    continue;
                }

                // Normalize phone number for database (convert 081395777706 to +6281395777706)
                $normalizedNumberForDb = PhoneNumberHelper::normalize($phoneNumber);
                if (!$normalizedNumberForDb) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($rowIndex + 2) . ": Invalid phone number format: " . $phoneNumber;
                    continue;
                }

                // Normalize phone number for chatId (convert 081395777706 to 6281395777706 for WAHA)
                // WAHA requires format: 6281395777706@c.us (without +)
                $normalizedNumberForChatId = PhoneNumberHelper::normalizeForChatId($phoneNumber);
                $chatId = $normalizedNumberForChatId . '@c.us';

                // Prepare message data
                $messageData = [
                    'user_id' => Auth::id(),
                    'session_id' => $session->id,
                    'from_number' => $fromNumber,
                    'to_number' => $normalizedNumberForDb,
                    'chat_type' => 'personal',
                    'message_type' => 'text',
                    'content' => $messageContent,
                    'direction' => 'outgoing',
                    'status' => 'pending',
                ];

                try {
                    // Create message record
                    $message = Message::create($messageData);

                    // Calculate delay for this message (if enabled)
                    $delay = 0;
                    if ($delayEnabled && $delaySeconds > 0) {
                        $delay = $rowIndex * $delaySeconds; // Cumulative delay
                    }

                    // Dispatch job with delay
                    if ($delay > 0) {
                        SendMessageJob::dispatch(
                            $message->id,
                            $session->id,
                            $chatId,
                            'text',
                            $messageContent,
                            null,
                            null,
                            null,
                            null,
                            'personal'
                        )->delay(now()->addSeconds($delay));
                    } else {
                        SendMessageJob::dispatch(
                            $message->id,
                            $session->id,
                            $chatId,
                            'text',
                            $messageContent,
                            null,
                            null,
                            null,
                            null,
                            'personal'
                        );
                    }

                    $results['success']++;

                    \Log::info('Bulk: Message job dispatched', [
                        'row' => $rowIndex + 2,
                        'phone' => $normalizedNumber,
                        'message_id' => $message->id,
                        'delay' => $delay,
                    ]);
                } catch (\Exception $e) {
                    $messageData['status'] = 'failed';
                    $errorMessage = $e->getMessage();
                    if (is_array($errorMessage)) {
                        $errorMessage = json_encode($errorMessage);
                    }
                    $messageData['error_message'] = $errorMessage;
                    Message::create($messageData);
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($rowIndex + 2) . ": " . $errorMessage;

                    \Log::error('Bulk: Error creating message', [
                        'row' => $rowIndex + 2,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Prepare success message
            $queuedCount = $results['success'];
            $successMessage = "Bulk messaging queued! Total: {$results['total']}, Queued: {$queuedCount}, Failed: {$results['failed']}. Messages will be sent in the background.";
            
            if ($results['failed'] > 0 && count($results['errors']) > 0) {
                $errorDetails = implode("\n", array_slice($results['errors'], 0, 10));
                if (count($results['errors']) > 10) {
                    $errorDetails .= "\n... and " . (count($results['errors']) - 10) . " more errors";
                }
                return redirect()->route('messages.index')
                    ->with('success', $successMessage)
                    ->with('error_details', $errorDetails);
            }

            return redirect()->route('messages.index')
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            \Log::error('Bulk: Error processing Excel file', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['excel_file' => 'Error processing Excel file: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified message.
     */
    public function show(Message $message)
    {
        if ($message->user_id !== Auth::id()) {
            abort(403);
        }

        return view('messages.show', compact('message'));
    }
}
