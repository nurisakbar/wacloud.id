<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
        } else {
            // Auto-regenerate once if API key doesn't have plain_key_encrypted (old API key)
            $hasPlainKey = !empty($apiKey->getAttributes()['plain_key_encrypted'] ?? null);
            
            if (!$hasPlainKey) {
                $newKey = $this->generateApiKey();
                $keyPrefix = substr($newKey, 0, 8);
                $newKeyHash = hash('sha256', $newKey);
                
                $apiKey->update([
                    'key' => $newKeyHash,
                    'key_prefix' => $keyPrefix,
                    'plain_key_encrypted' => Crypt::encryptString($newKey),
                ]);
                
                $apiKey->refresh();
            }
        }
        
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
            $newKeyHash = hash('sha256', $newKey);
            
            // Update API key with plain key encrypted in database
            // Store encrypted value directly to ensure it's saved
            $apiKey->update([
                'key' => $newKeyHash,
                'key_prefix' => $keyPrefix,
                'plain_key_encrypted' => Crypt::encryptString($newKey),
                'last_used_at' => null, // Reset last used
            ]);
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
        $keyHash = hash('sha256', $key);

        // Create API key with plain key encrypted in database
        // Store encrypted value directly to ensure it's saved
        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => $keyHash,
            'key_prefix' => $keyPrefix,
            'plain_key_encrypted' => Crypt::encryptString($key),
        ]);

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
