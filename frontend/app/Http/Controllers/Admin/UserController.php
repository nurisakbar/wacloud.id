<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserQuota;
use App\Models\WhatsAppSession;
use App\Models\QuotaPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\EnsureUserIsAdmin::class);
    }

    /**
     * Display list of users
     */
    public function index(Request $request)
    {
        $query = User::whereNotIn('role', ['admin', 'super_admin']);

        // Search by name, email, or phone
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        $users = $query->withCount(['whatsappSessions', 'messages'])
            ->latest()
            ->paginate(20);

        // Statistics
        $stats = [
            'total_users' => User::whereNotIn('role', ['admin', 'super_admin'])->count(),
            'active_users' => User::whereNotIn('role', ['admin', 'super_admin'])
                ->whereHas('whatsappSessions', function($q) {
                    $q->where('status', 'connected');
                })
                ->count(),
            'users_this_month' => User::whereNotIn('role', ['admin', 'super_admin'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show user details
     */
    public function show(User $user)
    {
        // Prevent viewing admin users
        if (in_array($user->role, ['admin', 'super_admin'])) {
            abort(404);
        }

        $user->load(['whatsappSessions', 'activeSubscription', 'subscriptionPlan', 'apiKeys']);
        
        $quota = $user->getQuota();
        
        $stats = [
            'total_sessions' => $user->whatsappSessions()->count(),
            'connected_sessions' => $user->whatsappSessions()->where('status', 'connected')->count(),
            'total_messages' => $user->messages()->count(),
            'messages_sent' => $user->messages()->where('direction', 'outgoing')->count(),
            'messages_received' => $user->messages()->where('direction', 'incoming')->count(),
        ];

        return view('admin.users.show', compact('user', 'quota', 'stats'));
    }

    /**
     * Top up quota for a user
     */
    public function topUpQuota(Request $request, User $user)
    {
        // Prevent top up for admin users
        if (in_array($user->role, ['admin', 'super_admin'])) {
            abort(404);
        }

        $request->validate([
            'balance' => 'nullable|numeric|min:0',
            'text_quota' => 'nullable|integer|min:0',
            'multimedia_quota' => 'nullable|integer|min:0',
            'free_text_quota' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if at least one quota type is provided
        $balance = (float) ($request->balance ?? 0);
        $textQuota = (int) ($request->text_quota ?? 0);
        $multimediaQuota = (int) ($request->multimedia_quota ?? 0);
        $freeTextQuota = (int) ($request->free_text_quota ?? 0);

        if ($balance == 0 && $textQuota == 0 && $multimediaQuota == 0 && $freeTextQuota == 0) {
            return back()->withErrors(['error' => 'Please provide at least one quota type to top up.']);
        }

        // Get or create user quota
        $quota = UserQuota::getOrCreateForUser($user->id);

        // Store old values for logging
        $oldBalance = $quota->balance;
        $oldTextQuota = $quota->text_quota;
        $oldMultimediaQuota = $quota->multimedia_quota;
        $oldFreeTextQuota = $quota->free_text_quota;

        // Add quota
        if ($balance > 0) {
            $quota->addBalance($balance);
        }
        if ($textQuota > 0) {
            $quota->addTextQuota($textQuota);
        }
        if ($multimediaQuota > 0) {
            $quota->addMultimediaQuota($multimediaQuota);
        }
        if ($freeTextQuota > 0) {
            $quota->addFreeTextQuota($freeTextQuota);
        }

        // Refresh quota to get updated values
        $quota->refresh();

        // Create purchase record for audit trail
        $notesParts = ['Admin top up'];
        if ($balance > 0) {
            $notesParts[] = "Balance: Rp " . number_format($balance, 0, ',', '.');
        }
        if ($textQuota > 0) {
            $notesParts[] = "Text Quota: " . number_format($textQuota, 0, ',', '.');
        }
        if ($multimediaQuota > 0) {
            $notesParts[] = "Multimedia Quota: " . number_format($multimediaQuota, 0, ',', '.');
        }
        if ($freeTextQuota > 0) {
            $notesParts[] = "Free Text Quota: " . number_format($freeTextQuota, 0, ',', '.');
        }
        if ($request->notes) {
            $notesParts[] = "Notes: " . $request->notes;
        }
        
        $purchase = QuotaPurchase::create([
            'user_id' => $user->id,
            'amount' => 0, // Admin top up is free
            'balance_added' => $balance,
            'text_quota_added' => $textQuota,
            'multimedia_quota_added' => $multimediaQuota,
            'payment_method' => 'admin_topup',
            'status' => 'completed',
            'notes' => implode(' | ', $notesParts),
            'completed_at' => now(),
        ]);

        Log::info('Admin top up quota', [
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'purchase_id' => $purchase->id,
            'balance_added' => $balance,
            'text_quota_added' => $textQuota,
            'multimedia_quota_added' => $multimediaQuota,
            'free_text_quota_added' => $freeTextQuota,
            'old_balance' => $oldBalance,
            'new_balance' => $quota->balance,
            'old_text_quota' => $oldTextQuota,
            'new_text_quota' => $quota->text_quota,
            'old_multimedia_quota' => $oldMultimediaQuota,
            'new_multimedia_quota' => $quota->multimedia_quota,
            'old_free_text_quota' => $oldFreeTextQuota,
            'new_free_text_quota' => $quota->free_text_quota,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Quota berhasil ditambahkan!');
    }
}

