<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneNumberHelper;
use App\Models\MessagePricingSetting;
use App\Models\QuotaPurchase;
use App\Models\UserQuota;
use App\Services\WACloudNotificationService;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuotaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display quota page
     */
    public function index()
    {
        $user = Auth::user();
        $quota = $user->getQuota();
        $pricing = MessagePricingSetting::getActive();

        return view('quota.index', compact('quota', 'pricing'));
    }

    /**
     * Get purchases data for DataTables (server-side)
     */
    public function purchasesDataTable(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->quotaPurchases()->latest();

        // Get DataTables parameters
        $draw = $request->get('draw');
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);
        $search = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir = $request->get('order')[0]['dir'] ?? 'desc';

        // Column mapping
        $columns = ['purchase_number', 'created_at', 'amount', 'status', 'id'];
        $orderBy = $columns[$orderColumn] ?? 'created_at';

        // Apply search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination
        $totalRecords = $user->quotaPurchases()->count();
        $filteredRecords = $query->count();

        // Apply ordering and pagination
        $purchases = $query->orderBy($orderBy, $orderDir)
                          ->skip($start)
                          ->take($length)
                          ->get();

        // Format data for DataTables
        $data = [];
        foreach ($purchases as $purchase) {
            // Status badge
            $statusBadge = '';
            if ($purchase->status === 'completed') {
                $statusBadge = '<span class="badge badge-success">Selesai</span>';
            } elseif ($purchase->status === 'pending_verification') {
                $statusBadge = '<span class="badge badge-info"><i class="fas fa-clock"></i> Menunggu Konfirmasi Admin</span>';
            } elseif ($purchase->status === 'waiting_payment') {
                $statusBadge = '<span class="badge badge-warning"><i class="fas fa-money-bill-wave"></i> Menunggu Pembayaran</span>';
            } elseif ($purchase->status === 'pending') {
                $statusBadge = '<span class="badge badge-warning">Menunggu</span>';
            } elseif ($purchase->status === 'failed') {
                $statusBadge = '<span class="badge badge-danger">Gagal</span>';
            } else {
                $statusBadge = '<span class="badge badge-secondary">' . ucfirst($purchase->status) . '</span>';
            }

            // Action buttons
            $actionButtons = '';
            if ($purchase->status === 'completed') {
                $actionButtons = '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Selesai</span>';
            } elseif ($purchase->status === 'pending_verification') {
                $actionButtons = '<span class="badge badge-info"><i class="fas fa-clock"></i> Menunggu verifikasi admin</span>';
            } elseif ($purchase->payment_method === 'xendit') {
                if ($purchase->xendit_invoice_url) {
                    if (in_array($purchase->status, ['pending', 'waiting_payment'])) {
                        $actionButtons = '<a href="' . $purchase->xendit_invoice_url . '" target="_blank" class="btn btn-sm btn-success" title="Klik untuk melakukan pembayaran via Xendit"><i class="fas fa-credit-card"></i> Bayar via Xendit</a>';
                    } else {
                        $actionButtons = '<a href="' . $purchase->xendit_invoice_url . '" target="_blank" class="btn btn-sm btn-outline-info" title="Lihat invoice Xendit"><i class="fas fa-external-link-alt"></i> Lihat Invoice</a>';
                    }
                } elseif (in_array($purchase->status, ['pending', 'waiting_payment'])) {
                    $actionButtons = '<button type="button" class="btn btn-sm btn-primary" onclick="createXenditInvoice(\'' . e($purchase->id) . '\')" id="btn-create-invoice-' . e($purchase->id) . '" title="Buat invoice pembayaran Xendit"><i class="fas fa-plus-circle"></i> Buat Invoice</button>';
                } else {
                    $actionButtons = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Invoice belum dibuat</span>';
                }
            } elseif ($purchase->payment_method === 'manual') {
                if (in_array($purchase->status, ['waiting_payment', 'pending'])) {
                    $confirmUrl = route('quota.confirm-payment', $purchase->id);
                    $actionButtons = '<a href="' . $confirmUrl . '" class="btn btn-sm btn-primary" title="Konfirmasi pembayaran manual dengan upload bukti transfer"><i class="fas fa-upload"></i> Konfirmasi Pembayaran</a>';
                } elseif ($purchase->status === 'pending_verification') {
                    $actionButtons = '<span class="badge badge-info"><i class="fas fa-clock"></i> Menunggu verifikasi admin</span>';
                } else {
                    $actionButtons = '<span class="badge badge-secondary"><i class="fas fa-info-circle"></i> Menunggu verifikasi</span>';
                }
            } else {
                $actionButtons = '<span class="text-muted">-</span>';
            }

            $data[] = [
                '<strong>' . e($purchase->purchase_number) . '</strong>',
                $purchase->created_at->format('d/m/Y H:i'),
                '<strong>Rp ' . number_format($purchase->amount, 0, ',', '.') . '</strong>',
                $statusBadge,
                '<div class="d-flex gap-1 flex-wrap">' . $actionButtons . '</div>',
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new quota purchase
     */
    public function create()
    {
        $user = Auth::user();
        $quota = $user->getQuota();
        $pricing = MessagePricingSetting::getActive();

        return view('quota.create', compact('quota', 'pricing'));
    }

    /**
     * Purchase quota
     */
    public function purchase(Request $request)
    {
        $request->validate([
            'text_quota_quantity' => 'nullable|integer|min:0',
            'multimedia_quota_quantity' => 'nullable|integer|min:0',
            'amount' => 'required|numeric|min:1000',
            'payment_method' => 'required|in:manual', // Xendit disabled
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $pricing = MessagePricingSetting::getActive();
        
        $textQuotaQuantity = (int) ($request->text_quota_quantity ?? 0);
        $multimediaQuotaQuantity = (int) ($request->multimedia_quota_quantity ?? 0);
        
        // Validate at least one quota type is selected
        if ($textQuotaQuantity == 0 && $multimediaQuotaQuantity == 0) {
            return back()->withErrors(['amount' => 'Silakan isi minimal satu jenis quota yang ingin dibeli.']);
        }
        
        $balanceAdded = 0;
        $textQuotaAdded = $textQuotaQuantity;
        $multimediaQuotaAdded = $multimediaQuotaQuantity;
        
        // Calculate total amount
        $calculatedAmount = 0;
        
        if ($textQuotaQuantity > 0) {
            $textPrice = $pricing->text_without_watermark_price;
            if ($textPrice <= 0) {
                return back()->withErrors(['text_quota_quantity' => 'Text quota pricing is not set. Please contact admin.']);
            }
            $calculatedAmount += $textPrice * $textQuotaQuantity;
        }
        
        if ($multimediaQuotaQuantity > 0) {
            $multimediaPrice = $pricing->multimedia_price;
            if ($multimediaPrice <= 0) {
                return back()->withErrors(['multimedia_quota_quantity' => 'Multimedia quota pricing is not set. Please contact admin.']);
            }
            $calculatedAmount += $multimediaPrice * $multimediaQuotaQuantity;
        }
        
        // Validate amount matches calculation (allow small rounding differences)
        $amount = (float) $request->amount;
        if (abs($amount - $calculatedAmount) > 0.01) {
            return back()->withErrors(['amount' => "Amount should be Rp " . number_format($calculatedAmount, 0, ',', '.') . " based on your selection."]);
        }

        // Determine initial status based on payment method
        $initialStatus = $request->payment_method === 'manual' ? 'waiting_payment' : 'pending';

        // Create purchase record
        $purchase = QuotaPurchase::create([
            'user_id' => Auth::id(),
            'amount' => $calculatedAmount,
            'balance_added' => $balanceAdded,
            'text_quota_added' => $textQuotaAdded,
            'multimedia_quota_added' => $multimediaQuotaAdded,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'notes' => $request->notes,
            'status' => $initialStatus,
        ]);

        Log::info('Quota purchase created', [
            'user_id' => Auth::id(),
            'purchase_id' => $purchase->id,
            'amount' => $calculatedAmount,
            'balance' => $balanceAdded,
            'text_quota' => $textQuotaAdded,
            'multimedia_quota' => $multimediaQuotaAdded,
        ]);

        // Handle payment method
        // Xendit payment is disabled
        if ($request->payment_method === 'xendit') {
            return back()->withErrors([
                'payment_method' => 'Pembayaran via Xendit saat ini dinonaktifkan. Silakan gunakan metode Manual Transfer.'
            ])->withInput();
        }
        
        if ($request->payment_method === 'manual') {
            // Send WhatsApp notification for manual payment (non-blocking)
            try {
                $this->sendOrderNotification($purchase, Auth::user(), $textQuotaAdded, $multimediaQuotaAdded, $calculatedAmount);
            } catch (\Exception $e) {
                // Log error but don't block the purchase process
                Log::error('Failed to send WhatsApp notification', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage(),
                ]);
            }
            // Manual payment - wait for admin approval
            $successMessage = "Permintaan pembelian berhasil dikirim! ";
            $successMessage .= "Pembelian Anda sedang menunggu persetujuan admin. ";
            $successMessage .= "Silakan selesaikan pembayaran dan tunggu admin untuk memverifikasi.";
            
            // Add warning if user doesn't have phone number
            if (empty(Auth::user()->phone)) {
                $successMessage .= " Catatan: Pastikan nomor telepon Anda terdaftar untuk menerima notifikasi WhatsApp.";
            }
            
            return redirect()->route('quota.index')
                ->with('success', $successMessage);
        } elseif ($request->payment_method === 'xendit') {
            // Xendit payment
            $xenditService = new XenditService();
            
            // Build description
            $description = "Purchase Quota - " . $purchase->purchase_number;
            if ($textQuotaAdded > 0) {
                $description .= " (Text Quota: " . $textQuotaAdded . " pesan)";
            }
            if ($multimediaQuotaAdded > 0) {
                $description .= " (Multimedia Quota: " . $multimediaQuotaAdded . " pesan)";
            }

            $invoiceResult = $xenditService->createInvoice([
                'external_id' => $purchase->purchase_number,
                'amount' => $calculatedAmount,
                'payer_email' => Auth::user()->email,
                'description' => $description,
                'success_url' => route('quota.payment.success', $purchase->id),
                'failure_url' => route('quota.payment.failure', $purchase->id),
                'customer' => [
                    'given_names' => Auth::user()->name,
                    'email' => Auth::user()->email,
                ],
            ]);

            if ($invoiceResult['success']) {
                $invoice = $invoiceResult['invoice'];
                
                // Update purchase with Xendit invoice info
                $purchase->update([
                    'xendit_invoice_id' => $invoice['id'],
                    'xendit_invoice_url' => $invoice['invoice_url'],
                ]);

                Log::info('Xendit invoice created for purchase', [
                    'purchase_id' => $purchase->id,
                    'invoice_id' => $invoice['id'],
                ]);

                // Send WhatsApp notification for Xendit payment (after invoice is created)
                try {
                    $this->sendOrderNotification($purchase->fresh(), Auth::user(), $textQuotaAdded, $multimediaQuotaAdded, $calculatedAmount);
                } catch (\Exception $e) {
                    // Log error but don't block the purchase process
                    Log::error('Failed to send WhatsApp notification for Xendit payment', [
                        'purchase_id' => $purchase->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // If AJAX request, return JSON
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'purchase_id' => $purchase->id,
                        'redirect' => $invoice['invoice_url'],
                        'invoice_url' => $invoice['invoice_url'],
                        'status_page' => route('payment.status', $purchase->id),
                    ]);
                }

                // Redirect to Xendit payment page
                return redirect($invoice['invoice_url']);
            } else {
                Log::error('Failed to create Xendit invoice', [
                    'purchase_id' => $purchase->id,
                    'error' => $invoiceResult['error'],
                ]);

                // If AJAX request, return JSON error
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Failed to create payment invoice: ' . $invoiceResult['error'],
                    ], 400);
                }

                return back()->withErrors(['payment' => 'Failed to create payment invoice: ' . $invoiceResult['error']]);
            }
        }
    }

    /**
     * Complete purchase (admin only - for manual verification)
     */
    public function completePurchase(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id() && !in_array(Auth::user()->role, ['admin', 'super_admin'])) {
            abort(403);
        }

        if ($purchase->status === 'completed') {
            return back()->with('error', 'Purchase already completed.');
        }

        $purchase->complete();

        Log::info('Quota purchase completed', [
            'purchase_id' => $purchase->id,
            'completed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Purchase completed successfully.');
    }

    /**
     * Handle Xendit payment success callback
     */
    public function paymentSuccess(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if already completed
        if ($purchase->status === 'completed') {
            return redirect()->route('quota.index')
                ->with('success', 'Payment already processed.');
        }

        // Verify with Xendit
        $xenditService = new XenditService();
        if ($purchase->xendit_invoice_id) {
            $invoiceResult = $xenditService->getInvoice($purchase->xendit_invoice_id);
            
            if ($invoiceResult['success']) {
                $invoice = $invoiceResult['invoice'];
                
                // Check if invoice is paid
                if ($invoice['status'] === 'PAID') {
                    $purchase->complete();
                    
                    return redirect()->route('quota.index')
                        ->with('success', 'Payment successful! Quota has been added to your account.');
                }
            }
        }

        return redirect()->route('quota.index')
            ->with('info', 'Payment is being processed. Please wait for confirmation.');
    }

    /**
     * Handle Xendit payment failure callback
     */
    public function paymentFailure(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        $purchase->update(['status' => 'failed']);

        return redirect()->route('quota.index')
            ->with('error', 'Payment failed. Please try again or contact support.');
    }

    /**
     * Handle Xendit webhook
     */
    public function webhook(Request $request)
    {
        Log::info('Xendit webhook received', [
            'payload' => $request->all(),
        ]);

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'invoice.paid') {
            $invoiceId = $data['id'] ?? null;
            $externalId = $data['external_id'] ?? null;

            if (!$invoiceId || !$externalId) {
                Log::warning('Xendit webhook missing required data', [
                    'invoice_id' => $invoiceId,
                    'external_id' => $externalId,
                ]);
                return response()->json(['error' => 'Missing required data'], 400);
            }

            // Find purchase by purchase_number (external_id)
            $purchase = QuotaPurchase::where('purchase_number', $externalId)
                ->where('xendit_invoice_id', $invoiceId)
                ->first();

            if (!$purchase) {
                Log::warning('Xendit webhook: Purchase not found', [
                    'invoice_id' => $invoiceId,
                    'external_id' => $externalId,
                ]);
                return response()->json(['error' => 'Purchase not found'], 404);
            }

            // Complete purchase if not already completed
            if ($purchase->status !== 'completed') {
                $purchase->complete();
                
                Log::info('Xendit webhook: Purchase completed', [
                    'purchase_id' => $purchase->id,
                    'invoice_id' => $invoiceId,
                ]);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true, 'message' => 'Event not handled']);
    }

    /**
     * Show payment confirmation form
     */
    public function showConfirmPayment(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($purchase->status, ['waiting_payment', 'pending', 'pending_verification'])) {
            return redirect()->route('quota.index')
                ->with('error', 'Purchase is not waiting for payment. Cannot confirm payment.');
        }

        if ($purchase->payment_method !== 'manual') {
            return redirect()->route('quota.index')
                ->with('error', 'This purchase is not using manual payment method.');
        }

        return view('quota.confirm-payment', compact('purchase'));
    }

    /**
     * Confirm manual payment
     */
    public function confirmPayment(Request $request, QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($purchase->status, ['pending', 'waiting_payment'])) {
            return back()->withErrors(['error' => 'Purchase is not pending or waiting for payment. Cannot confirm payment.']);
        }

        if ($purchase->payment_method !== 'manual') {
            return back()->withErrors(['error' => 'This purchase is not using manual payment method.']);
        }

        $request->validate([
            'payment_reference' => 'required|string|max:255',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'notes' => 'nullable|string|max:1000',
        ]);

        // Upload payment proof
        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $filename = 'payment_proof_' . $purchase->purchase_number . '_' . time() . '.' . $file->getClientOriginalExtension();
            $paymentProofPath = $file->storeAs('payment_proofs', $filename, 'public');
        }

        // Update purchase with payment confirmation and change status to pending_verification
        $purchase->update([
            'payment_reference' => $request->payment_reference,
            'payment_proof' => $paymentProofPath,
            'status' => 'pending_verification',
            'notes' => ($purchase->notes ? $purchase->notes . "\n\n" : '') . 
                      'Payment Confirmation: ' . ($request->notes ?? ''),
        ]);

        Log::info('Manual payment confirmed', [
            'purchase_id' => $purchase->id,
            'user_id' => Auth::id(),
            'payment_reference' => $request->payment_reference,
        ]);

        // Send notification to admin about payment confirmation
        try {
            $this->sendPaymentConfirmationNotification($purchase, Auth::user());
        } catch (\Exception $e) {
            // Log error but don't block the process
            Log::error('Failed to send payment confirmation notification to admin', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('quota.index')
            ->with('success', 'Payment confirmation submitted successfully! Please wait for admin approval.');
    }

    /**
     * Send WhatsApp notification for order
     *
     * @param QuotaPurchase $purchase
     * @param \App\Models\User $user
     * @param int $textQuotaAdded
     * @param int $multimediaQuotaAdded
     * @param float $amount
     * @return void
     */
    protected function sendOrderNotification($purchase, $user, $textQuotaAdded, $multimediaQuotaAdded, $amount)
    {
        Log::info('sendOrderNotification called', [
            'user_id' => $user->id,
            'purchase_id' => $purchase->id,
            'user_phone' => $user->phone ?? 'not set',
            'api_key_set' => !empty(\App\Models\Setting::getValue('notification_api_key')),
            'device_id_set' => !empty(\App\Models\Setting::getValue('notification_device_id')),
        ]);
        
        // Check if user has phone number
        if (empty($user->phone)) {
            Log::warning('WhatsApp notification skipped: User has no phone number', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'purchase_id' => $purchase->id,
                'purchase_number' => $purchase->purchase_number,
            ]);
            return;
        }

        // Normalize phone number
        $phoneNumber = PhoneNumberHelper::normalize($user->phone);
        if (!$phoneNumber) {
            Log::warning('WhatsApp notification skipped: Invalid phone number format', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'purchase_id' => $purchase->id,
            ]);
            return;
        }

        // Get confirmation link URL
        $confirmPaymentUrl = route('quota.confirm-payment', $purchase->id);
        
        // Build notification message
        $message = "Halo {$user->name},\n\n";
        $message .= "Terima kasih atas pembelian quota Anda!\n\n";
        $message .= "📋 *Detail Pembelian:*\n";
        $message .= "Nomor Pembelian: {$purchase->purchase_number}\n";
        $message .= "Tanggal: " . $purchase->created_at->format('d/m/Y H:i') . "\n";
        $message .= "Total: Rp " . number_format($amount, 0, ',', '.') . "\n\n";
        
        if ($textQuotaAdded > 0) {
            $message .= "📝 Quota Teks: " . number_format($textQuotaAdded, 0, ',', '.') . " pesan\n";
        }
        if ($multimediaQuotaAdded > 0) {
            $message .= "🖼️ Quota Multimedia: " . number_format($multimediaQuotaAdded, 0, ',', '.') . " pesan\n";
        }
        
        $message .= "\n";
        
        if ($purchase->payment_method === 'manual') {
            $message .= "💳 *Metode Pembayaran:* Transfer Manual\n";
            $message .= "Status: Menunggu Pembayaran\n\n";
            $message .= "🏦 *Instruksi Pembayaran:*\n";
            $message .= "Silakan transfer ke rekening berikut:\n\n";
            $message .= "Bank: Mandiri\n";
            $message .= "No. Rekening: *1320025238651*\n";
            $message .= "Atas Nama: *NURIS AKBAR*\n\n";
            $message .= "Setelah melakukan transfer, silakan konfirmasi pembayaran Anda melalui link berikut:\n";
            $message .= "🔗 {$confirmPaymentUrl}\n\n";
            $message .= "Atau konfirmasi manual ke WhatsApp: 089699935552\n\n";
        } elseif ($purchase->payment_method === 'xendit') {
            $message .= "💳 *Metode Pembayaran:* Xendit (Online Payment)\n";
            $message .= "Status: Menunggu Pembayaran\n\n";
            
            if ($purchase->xendit_invoice_url) {
                $message .= "🔗 *Link Pembayaran Xendit:*\n";
                $message .= "Silakan klik link berikut untuk melakukan pembayaran:\n";
                $message .= "{$purchase->xendit_invoice_url}\n\n";
                $message .= "Atau salin link di atas dan buka di browser Anda.\n\n";
            } else {
                $message .= "⚠️ Invoice pembayaran sedang diproses. Silakan tunggu beberapa saat.\n\n";
            }
        }
        
        $message .= "Terima kasih telah menggunakan layanan kami! 🙏";

        // Send notification
        $notificationService = new WACloudNotificationService();
        $result = $notificationService->sendNotification($phoneNumber, $message);

        if ($result['success']) {
            Log::info('WhatsApp notification sent successfully', [
                'user_id' => $user->id,
                'purchase_id' => $purchase->id,
                'phone' => $phoneNumber,
            ]);
        } else {
            Log::error('Failed to send WhatsApp notification', [
                'user_id' => $user->id,
                'purchase_id' => $purchase->id,
                'phone' => $phoneNumber,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }

    /**
     * Send WhatsApp notification to admin about payment confirmation
     *
     * @param QuotaPurchase $purchase
     * @param \App\Models\User $user
     * @return void
     */
    protected function sendPaymentConfirmationNotification($purchase, $user)
    {
        // Get admin phone number from config or use default
        $adminPhone = config('services.wacloud.admin_phone', '089699935552');
        
        // Normalize phone number
        $phoneNumber = PhoneNumberHelper::normalize($adminPhone);
        if (!$phoneNumber) {
            Log::warning('Admin payment confirmation notification skipped: Invalid admin phone number', [
                'admin_phone' => $adminPhone,
                'purchase_id' => $purchase->id,
            ]);
            return;
        }

        // Get admin panel URLs
        $baseUrl = config('app.url');
        $adminPurchaseUrl = route('admin.quota-purchases.show', $purchase->id);
        $adminApproveUrl = route('admin.quota-purchases.approve.get', $purchase->id);
        $adminListUrl = route('admin.quota-purchases.index', ['status' => 'pending_verification']);

        // Build notification message
        $message = "🔔 *Notifikasi Konfirmasi Pembayaran*\n\n";
        $message .= "Ada konfirmasi pembayaran baru yang membutuhkan verifikasi!\n\n";
        $message .= "📋 *Detail Pembelian:*\n";
        $message .= "Nomor Pembelian: {$purchase->purchase_number}\n";
        $message .= "Pelanggan: {$user->name}\n";
        $message .= "Email: {$user->email}\n";
        $message .= "Tanggal Pembelian: " . $purchase->created_at->format('d/m/Y H:i') . "\n";
        $message .= "Total: Rp " . number_format($purchase->amount, 0, ',', '.') . "\n\n";
        
        if ($purchase->text_quota_added > 0) {
            $message .= "📝 Quota Teks: " . number_format($purchase->text_quota_added, 0, ',', '.') . " pesan\n";
        }
        if ($purchase->multimedia_quota_added > 0) {
            $message .= "🖼️ Quota Multimedia: " . number_format($purchase->multimedia_quota_added, 0, ',', '.') . " pesan\n";
        }
        
        $message .= "\n";
        $message .= "💳 *Detail Konfirmasi Pembayaran:*\n";
        $message .= "Referensi: {$purchase->payment_reference}\n";
        $message .= "Status: Menunggu Verifikasi\n\n";
        
        if ($purchase->notes) {
            $message .= "📝 Catatan: " . substr($purchase->notes, 0, 200) . "\n\n";
        }
        
        $message .= "🔗 *Tindakan Cepat:*\n";
        $message .= "✅ Approve langsung:\n";
        $message .= "{$adminApproveUrl}\n\n";
        $message .= "👁️ Lihat detail:\n";
        $message .= "{$adminPurchaseUrl}\n\n";
        $message .= "📋 Lihat semua pending:\n";
        $message .= "{$adminListUrl}\n\n";
        
        $message .= "Terima kasih! 🙏";

        // Send notification
        $notificationService = new WACloudNotificationService();
        $result = $notificationService->sendNotification($phoneNumber, $message);

        if ($result['success']) {
            Log::info('Payment confirmation notification sent to admin successfully', [
                'purchase_id' => $purchase->id,
                'admin_phone' => $phoneNumber,
            ]);
        } else {
            Log::error('Failed to send payment confirmation notification to admin', [
                'purchase_id' => $purchase->id,
                'admin_phone' => $phoneNumber,
                'error' => $result['error'] ?? 'Unknown error',
            ]);
        }
    }
}
