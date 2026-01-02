<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuotaPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuotaPurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\EnsureUserIsAdmin::class);
    }

    /**
     * Display list of quota purchases
     */
    public function index(Request $request)
    {
        $query = QuotaPurchase::with('user')->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->where('payment_method', $request->payment_method);
        }

        // Search by purchase number or user name
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->paginate(20);

        // Statistics
        $stats = [
            'pending' => QuotaPurchase::where('status', 'pending')->count(),
            'waiting_payment' => QuotaPurchase::where('status', 'waiting_payment')->count(),
            'pending_verification' => QuotaPurchase::where('status', 'pending_verification')->count(),
            'completed' => QuotaPurchase::where('status', 'completed')->count(),
            'failed' => QuotaPurchase::where('status', 'failed')->count(),
            'total_amount' => QuotaPurchase::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.quota-purchases.index', compact('purchases', 'stats'));
    }

    /**
     * Show purchase details
     */
    public function show(QuotaPurchase $quotaPurchase)
    {
        $quotaPurchase->load('user');
        return view('admin.quota-purchases.show', compact('quotaPurchase'));
    }

    /**
     * Approve purchase (complete it)
     */
    public function approve(QuotaPurchase $quotaPurchase)
    {
        if ($quotaPurchase->status === 'completed') {
            return back()->with('error', 'Purchase already completed.');
        }

        if ($quotaPurchase->status === 'failed') {
            return back()->with('error', 'Cannot approve a failed purchase.');
        }

        if (!in_array($quotaPurchase->status, ['waiting_payment', 'pending', 'pending_verification'])) {
            return back()->with('error', 'Can only approve waiting payment, pending or pending verification purchases.');
        }

        $quotaPurchase->complete();

        Log::info('Quota purchase approved by admin', [
            'purchase_id' => $quotaPurchase->id,
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', 'Purchase approved and quota has been added to user account.');
    }

    /**
     * Approve purchase via GET (for direct link access from WhatsApp)
     */
    public function approveGet(QuotaPurchase $quotaPurchase)
    {
        if ($quotaPurchase->status === 'completed') {
            return redirect()->route('admin.quota-purchases.show', $quotaPurchase)
                ->with('error', 'Purchase already completed.');
        }

        if ($quotaPurchase->status === 'failed') {
            return redirect()->route('admin.quota-purchases.show', $quotaPurchase)
                ->with('error', 'Cannot approve a failed purchase.');
        }

        if (!in_array($quotaPurchase->status, ['waiting_payment', 'pending', 'pending_verification'])) {
            return redirect()->route('admin.quota-purchases.show', $quotaPurchase)
                ->with('error', 'Can only approve waiting payment, pending or pending verification purchases.');
        }

        $quotaPurchase->complete();

        Log::info('Quota purchase approved by admin via GET link', [
            'purchase_id' => $quotaPurchase->id,
            'approved_by' => auth()->id() ?? 'via_direct_link',
        ]);

        return redirect()->route('admin.quota-purchases.show', $quotaPurchase)
            ->with('success', 'Purchase approved and quota has been added to user account.');
    }

    /**
     * Reject purchase
     */
    public function reject(Request $request, QuotaPurchase $quotaPurchase)
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        if ($quotaPurchase->status === 'completed') {
            return back()->with('error', 'Cannot reject a completed purchase.');
        }

        $quotaPurchase->update([
            'status' => 'failed',
            'notes' => ($quotaPurchase->notes ? $quotaPurchase->notes . "\n\n" : '') . 
                      'Rejected by admin: ' . ($request->rejection_reason ?? 'No reason provided'),
        ]);

        Log::info('Quota purchase rejected by admin', [
            'purchase_id' => $quotaPurchase->id,
            'rejected_by' => auth()->id(),
            'reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Purchase rejected successfully.');
    }
}
