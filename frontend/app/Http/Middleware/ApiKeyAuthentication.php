<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from X-Api-Key header only
        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key is required',
                'message' => 'Please provide your API key in the X-Api-Key header',
            ], 401);
        }

        // Trim whitespace
        $apiKey = trim($apiKey);

        // Hash the provided key to compare with stored hash
        $hashedKey = hash('sha256', $apiKey);

        // Find API key
        $key = ApiKey::where('key', $hashedKey)
            ->where('is_active', true)
            ->first();

        if (!$key) {
            // Log for debugging (only in non-production)
            if (config('app.debug')) {
                \Log::warning('Invalid API key attempt', [
                    'provided_key_length' => strlen($apiKey),
                    'provided_key_prefix' => substr($apiKey, 0, 10),
                    'hashed_key' => $hashedKey,
                ]);
            }
            
            // Check if user might be sending hash instead of plain key
            $isHashFormat = (strlen($apiKey) === 64 && ctype_xdigit($apiKey));
            
            // Provide helpful error message based on format
            if ($isHashFormat) {
                $errorMessage = 'The provided API key is invalid or inactive. It looks like you are sending a hash. Please use the plain API key that was shown when you created it in the API Keys page.';
            } else {
                $errorMessage = 'The provided API key is invalid or inactive. Make sure you are using the full API key that was shown when you created it in the API Keys page.';
            }
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
                'message' => $errorMessage,
            ], 401);
        }

        // Check if key is expired
        if ($key->expires_at && $key->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'error' => 'API key expired',
                'message' => 'Your API key has expired',
            ], 401);
        }

        // Update last used timestamp
        $key->update(['last_used_at' => now()]);

        // Attach user and API key to request for use in controllers
        $request->merge([
            'api_key' => $key,
            'user' => $key->user,
        ]);

        // Set user for Auth facade (optional, for compatibility)
        auth()->setUser($key->user);

        return $next($request);
    }
}


