<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class KlikmedisController extends Controller
{
    /**
     * Show authorization page
     */
    public function ssoAuthorize(Request $request)

    {
        $request->validate([
            'setting_id' => 'required',
            'callback_url' => 'required|url',
            'name' => 'required',
            'email' => 'required|email',
        ]);

        return view('klikmedis.authorize', [
            'setting_id' => $request->setting_id,
            'callback_url' => $request->callback_url,
            'name' => $request->name,
            'email' => $request->email,
        ]);
    }

    /**
     * Confirm synchronization and redirect back
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'setting_id' => 'required',
            'callback_url' => 'required|url',
            'name' => 'required',
            'email' => 'required|email',
        ]);

        // Find or create user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(16)),
                'role' => 'user',
            ]);
        }

        // Login user
        Auth::login($user);

        // Get or create API key
        $apiKey = $user->apiKeys()->first();
        if (!$apiKey) {
            $key = Str::random(64);
            $keyPrefix = substr($key, 0, 8);
            $keyHash = hash('sha256', $key);

            $apiKey = ApiKey::create([
                'user_id' => $user->id,
                'name' => 'Klikmedis Integration',
                'key' => $keyHash,
                'key_prefix' => $keyPrefix,
                'plain_key_encrypted' => Crypt::encryptString($key),
            ]);
            $plainKey = $key;
        } else {
            $plainKey = Crypt::decryptString($apiKey->plain_key_encrypted);
        }

        // Redirect back with success status and API key
        $separator = str_contains($request->callback_url, '?') ? '&' : '?';
        $redirectUrl = $request->callback_url . $separator . http_build_query([
            'status' => 'success',
            'api_key' => $plainKey,
            'setting_id' => $request->setting_id,
        ]);

        return redirect()->away($redirectUrl);
    }
}
