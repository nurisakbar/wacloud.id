<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'key_prefix',
        'plain_key_encrypted',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the decrypted plain API key from database
     */
    public function getPlainKeyAttribute(): ?string
    {
        // Get directly from database attributes
        $encrypted = $this->getAttributes()['plain_key_encrypted'] ?? null;
        
        if (!$encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt API key', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Set the encrypted plain API key to database
     */
    public function setPlainKeyAttribute(string $value): void
    {
        $this->attributes['plain_key_encrypted'] = Crypt::encryptString($value);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }
}
