<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DebugLog
{
    /**
     * Check whether debug mode is currently enabled (cached 60 s).
     * Works in all environments — relies only on the admin setting, not APP_ENV.
     */
    public static function isEnabled(): bool
    {
        return Cache::remember('debug_mode_enabled', 60, function () {
            return (bool) Setting::getValue('debug_mode', false);
        });
    }

    /**
     * Write a debug log entry — only when admin debug toggle is ON.
     *
     * Uses Log::info (not Log::debug) so it is NEVER filtered by
     * LOG_LEVEL=info in production. Output is tagged with [DEBUG] prefix
     * to distinguish it from normal info logs.
     *
     * Usage (drop-in replacement for Log::debug):
     *   DebugLog::log('My message', ['key' => 'value']);
     */
    public static function log(string $message, array $context = []): void
    {
        if (self::isEnabled()) {
            Log::info('[DEBUG] ' . $message, $context);
        }
    }

    /**
     * Toggle debug mode on/off and flush the cache.
     * Returns the new state (true = ON).
     */
    public static function toggle(): bool
    {
        $current = self::isEnabled();
        $new     = !$current;

        Setting::setValue('debug_mode', $new ? '1' : '0', 'Mode debug global (aktifkan untuk log detail)');
        Cache::forget('debug_mode_enabled');

        Log::info('[DebugLog] Debug mode changed', [
            'from'  => $current ? 'ON' : 'OFF',
            'to'    => $new     ? 'ON' : 'OFF',
            'admin' => auth()->id() ?? 'system',
        ]);

        return $new;
    }
}
