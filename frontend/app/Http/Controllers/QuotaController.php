<?php

namespace App\Http\Controllers;

use App\Models\MessagePricingSetting;
use App\Models\QuotaPurchase;
use App\Models\UserQuota;
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
        $purchases = $user->quotaPurchases()->latest()->paginate(10);

        return view('quota.index', compact('quota', 'pricing', 'purchases'));
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
            'payment_method' => 'required|in:manual,xendit',
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
        if ($request->payment_method === 'manual') {
            // Manual payment - wait for admin approval
            $successMessage = "Purchase request submitted successfully! ";
            $successMessage .= "Your purchase is pending admin approval. ";
            $successMessage .= "Please complete the payment and wait for admin to verify.";
            
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

        if ($purchase->status !== 'pending') {
            return back()->withErrors(['error' => 'Purchase is not pending. Cannot confirm payment.']);
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

        return redirect()->route('quota.index')
            ->with('success', 'Payment confirmation submitted successfully! Please wait for admin approval.');
    }
}
