<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(\App\Http\Middleware\EnsureUserIsAdmin::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Voucher::with('creator')->withCount('redemptions');

        // Search
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'active') {
                $query->where('is_active', true)
                      ->where(function($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      })
                      ->where(function($q) {
                          $q->whereNull('max_uses')
                            ->orWhereColumn('used_count', '<', 'max_uses');
                      });
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<=', now());
            } elseif ($request->status === 'used_up') {
                $query->whereColumn('used_count', '>=', 'max_uses');
            }
        }

        $vouchers = $query->latest()->paginate(20);

        return view('admin.vouchers.index', compact('vouchers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.vouchers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'text_quota' => 'required|integer|min:0',
            'multimedia_quota' => 'required|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        // Ensure at least one quota is provided
        if ($request->text_quota == 0 && $request->multimedia_quota == 0) {
            return back()->withErrors(['error' => 'Minimal salah satu jenis quota harus diisi.'])->withInput();
        }

        $voucher = Voucher::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'text_quota' => $request->text_quota,
            'multimedia_quota' => $request->multimedia_quota,
            'max_uses' => $request->max_uses,
            'expires_at' => $request->expires_at,
            'is_active' => $request->has('is_active'),
            'created_by' => auth()->id(),
        ]);

        Log::info('Voucher created', [
            'admin_id' => auth()->id(),
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
        ]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        $voucher->load(['creator', 'redemptions.user']);
        return view('admin.vouchers.show', compact('voucher'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        return view('admin.vouchers.edit', compact('voucher'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code,' . $voucher->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'text_quota' => 'required|integer|min:0',
            'multimedia_quota' => 'required|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Ensure at least one quota is provided
        if ($request->text_quota == 0 && $request->multimedia_quota == 0) {
            return back()->withErrors(['error' => 'Minimal salah satu jenis quota harus diisi.'])->withInput();
        }

        $voucher->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'text_quota' => $request->text_quota,
            'multimedia_quota' => $request->multimedia_quota,
            'max_uses' => $request->max_uses,
            'expires_at' => $request->expires_at,
            'is_active' => $request->has('is_active'),
        ]);

        Log::info('Voucher updated', [
            'admin_id' => auth()->id(),
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
        ]);

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        Log::info('Voucher deleted', [
            'admin_id' => auth()->id(),
            'voucher_id' => $voucher->id,
            'code' => $voucher->code,
        ]);

        $voucher->delete();

        return redirect()->route('admin.vouchers.index')
            ->with('success', 'Voucher berhasil dihapus!');
    }
}
