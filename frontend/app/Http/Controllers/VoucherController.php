<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show redeem voucher page
     */
    public function index()
    {
        $user = auth()->user();
        $redemptions = $user->voucherRedemptions()->with('voucher')->latest()->paginate(10);
        
        return view('vouchers.index', compact('redemptions'));
    }

    /**
     * Redeem a voucher
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $code = strtoupper(trim($request->code));
        $voucher = Voucher::where('code', $code)->first();

        if (!$voucher) {
            return back()->withErrors(['code' => 'Kode voucher tidak ditemukan.'])->withInput();
        }

        $result = $voucher->redeem(auth()->user());

        if ($result['success']) {
            Log::info('Voucher redeemed', [
                'user_id' => auth()->id(),
                'voucher_id' => $voucher->id,
                'code' => $voucher->code,
                'text_quota' => $result['text_quota'],
                'multimedia_quota' => $result['multimedia_quota'],
            ]);

            return redirect()->route('vouchers.index')
                ->with('success', $result['message'] . ' Anda mendapatkan ' . 
                    ($result['text_quota'] > 0 ? $result['text_quota'] . ' Text Quota' : '') . 
                    ($result['text_quota'] > 0 && $result['multimedia_quota'] > 0 ? ' dan ' : '') . 
                    ($result['multimedia_quota'] > 0 ? $result['multimedia_quota'] . ' Multimedia Quota' : '') . '!');
        }

        return back()->withErrors(['code' => $result['error']])->withInput();
    }
}
