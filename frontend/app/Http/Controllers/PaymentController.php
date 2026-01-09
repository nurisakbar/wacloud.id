<?php

namespace App\Http\Controllers;

use App\Models\QuotaPurchase;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Create Xendit payment invoice
     */
    public function createInvoice(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:quota_purchases,id',
        ]);

        $purchase = QuotaPurchase::findOrFail($request->purchase_id);

        // Check ownership
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Check if already has invoice
        if ($purchase->xendit_invoice_id) {
            return response()->json([
                'success' => true,
                'invoice_url' => $purchase->xendit_invoice_url,
                'invoice_id' => $purchase->xendit_invoice_id,
            ]);
        }

        // Check if purchase is valid for payment
        if (!in_array($purchase->status, ['pending', 'waiting_payment'])) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase is not eligible for payment',
            ], 400);
        }

        // Build description
        $description = "Purchase Quota - " . $purchase->purchase_number;
        $items = [];
        
        if ($purchase->text_quota_added > 0) {
            $description .= " (Text Quota: " . $purchase->text_quota_added . " pesan)";
            $items[] = [
                'name' => 'Text Quota',
                'quantity' => $purchase->text_quota_added,
                'price' => $purchase->amount / ($purchase->text_quota_added + $purchase->multimedia_quota_added),
            ];
        }
        if ($purchase->multimedia_quota_added > 0) {
            $description .= " (Multimedia Quota: " . $purchase->multimedia_quota_added . " pesan)";
            $items[] = [
                'name' => 'Multimedia Quota',
                'quantity' => $purchase->multimedia_quota_added,
                'price' => $purchase->amount / ($purchase->text_quota_added + $purchase->multimedia_quota_added),
            ];
        }

        $invoiceResult = $this->xenditService->createInvoice([
            'external_id' => $purchase->purchase_number,
            'amount' => $purchase->amount,
            'payer_email' => Auth::user()->email,
            'description' => $description,
            'success_url' => route('payment.success', $purchase->id),
            'failure_url' => route('payment.failure', $purchase->id),
            'customer' => [
                'given_names' => Auth::user()->name,
                'email' => Auth::user()->email,
            ],
            'items' => $items,
        ]);

        if ($invoiceResult['success']) {
            $invoice = $invoiceResult['invoice'];
            
            // Update purchase with Xendit invoice info
            $purchase->update([
                'xendit_invoice_id' => $invoice['id'],
                'xendit_invoice_url' => $invoice['invoice_url'],
                'status' => 'pending',
            ]);

            Log::info('Xendit invoice created for purchase', [
                'purchase_id' => $purchase->id,
                'invoice_id' => $invoice['id'],
            ]);

            return response()->json([
                'success' => true,
                'invoice_url' => $invoice['invoice_url'],
                'invoice_id' => $invoice['id'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $invoiceResult['error'] ?? 'Failed to create invoice',
        ], 400);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // If no invoice ID, return current status
        if (!$purchase->xendit_invoice_id) {
            return response()->json([
                'success' => true,
                'status' => $purchase->status,
                'paid' => false,
            ]);
        }

        // Check with Xendit
        $invoiceResult = $this->xenditService->getInvoice($purchase->xendit_invoice_id);

        if ($invoiceResult['success']) {
            $invoice = $invoiceResult['invoice'];
            $isPaid = $invoice['status'] === 'PAID';
            
            // Update purchase status if paid
            if ($isPaid && $purchase->status !== 'completed') {
                $purchase->complete();
            }

            return response()->json([
                'success' => true,
                'status' => $purchase->status,
                'invoice_status' => $invoice['status'],
                'paid' => $isPaid,
                'invoice' => $invoice,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $invoiceResult['error'] ?? 'Failed to check status',
        ], 400);
    }

    /**
     * Payment success page
     */
    public function success(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Verify with Xendit
        if ($purchase->xendit_invoice_id) {
            $invoiceResult = $this->xenditService->getInvoice($purchase->xendit_invoice_id);
            
            if ($invoiceResult['success']) {
                $invoice = $invoiceResult['invoice'];
                
                // Check if invoice is paid
                if ($invoice['status'] === 'PAID' && $purchase->status !== 'completed') {
                    $purchase->complete();
                    
                    return redirect()->route('quota.index')
                        ->with('success', 'Payment successful! Quota has been added to your account.');
                }
            }
        }

        return view('payment.success', compact('purchase'));
    }

    /**
     * Payment failure page
     */
    public function failure(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $purchase->update(['status' => 'failed']);

        return view('payment.failure', compact('purchase'));
    }

    /**
     * Payment status page (loading/polling)
     */
    public function status(QuotaPurchase $purchase)
    {
        if ($purchase->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return view('payment.status', compact('purchase'));
    }
}






