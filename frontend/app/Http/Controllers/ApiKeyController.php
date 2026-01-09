<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Auto-create API key if user doesn't have one
        $apiKey = $user->apiKeys()->first();
        
        if (!$apiKey) {
            $apiKey = $this->createApiKey($user, 'My API Key');
        }
        // Note: We don't regenerate if plain key is not in session
        // Plain key is only shown when newly created or regenerated
        // If user wants to see their key again, they must regenerate it explicitly
        
        return view('api-keys.index', compact('apiKey'));
    }

    public function regenerate()
    {
        $user = Auth::user();
        
        // Ensure only the owner can regenerate their API key
        $apiKey = $user->apiKeys()->first();
        
        if (!$apiKey) {
            // Create new if doesn't exist
            $apiKey = $this->createApiKey($user, 'My API Key');
        } else {
            // Verify ownership before regenerating
            if ($apiKey->user_id !== $user->id) {
                abort(403, 'Unauthorized: You can only regenerate your own API key.');
            }
            
            // Regenerate existing key
            $newKey = $this->generateApiKey();
            $keyPrefix = substr($newKey, 0, 8);
            
            $apiKey->update([
                'key' => hash('sha256', $newKey),
                'key_prefix' => $keyPrefix,
                'last_used_at' => null, // Reset last used
            ]);
            
            // Store the plain key in session permanently
            $userApiKeys = session('user_api_keys', []);
            $userApiKeys[$apiKey->id] = $newKey;
            session(['user_api_keys' => $userApiKeys]);
            
            // Also keep flash for backward compatibility
            session()->flash('api_key_plain', $newKey);
            session()->flash('api_key_id', $apiKey->id);
        }
        
        return redirect()->route('api-keys.index')->with('success', 'API key berhasil di-regenerate. Key lama tidak akan berfungsi lagi.');
    }

    /**
     * Generate a new API key
     * Format: 64 random characters
     */
    private function generateApiKey(): string
    {
        return Str::random(64);
    }

    /**
     * Create a new API key for the user
     */
    private function createApiKey($user, $name = 'My API Key')
    {
        // Generate API key
        $key = $this->generateApiKey();
        $keyPrefix = substr($key, 0, 8);

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => hash('sha256', $key),
            'key_prefix' => $keyPrefix,
        ]);

        // Store the plain key in session temporarily to show to user
        $userApiKeys = session('user_api_keys', []);
        $userApiKeys[$apiKey->id] = $key;
        session(['user_api_keys' => $userApiKeys]);
        
        // Also keep flash for backward compatibility
        session()->flash('api_key_plain', $key);
        session()->flash('api_key_id', $apiKey->id);

        return $apiKey;
    }

    // Legacy methods - kept for backward compatibility but redirect to index
    public function create()
    {
        return redirect()->route('api-keys.index');
    }

    public function show(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) abort(403);
        return redirect()->route('api-keys.index');
    }

    public function edit(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) abort(403);
        return redirect()->route('api-keys.index');
    }

    public function update(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) abort(403);
        return redirect()->route('api-keys.index');
    }

    public function store(Request $request)
    {
        // Redirect to regenerate since user can only have 1 key
        return redirect()->route('api-keys.regenerate');
    }

    public function destroy(ApiKey $apiKey)
    {
        if ($apiKey->user_id !== Auth::id()) abort(403);
        
        // Don't allow deletion, only regeneration
        return back()->withErrors(['error' => 'You cannot delete your API key. Use regenerate to create a new one.']);
    }
}
