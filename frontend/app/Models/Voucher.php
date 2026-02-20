<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Voucher extends Model
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'code',
        'name',
        'description',
        'text_quota',
        'multimedia_quota',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'text_quota' => 'integer',
            'multimedia_quota' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the admin who created this voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the redemptions for this voucher.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /**
     * Check if voucher is valid (active, not expired, not exceeded max uses)
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if user has already redeemed this voucher
     */
    public function isRedeemedBy(User $user): bool
    {
        return $this->redemptions()->where('user_id', $user->id)->exists();
    }

    /**
     * Redeem voucher for a user
     */
    public function redeem(User $user): array
    {
        // Check if voucher is active
        if (!$this->is_active) {
            return [
                'success' => false,
                'error' => 'Voucher tidak aktif',
            ];
        }

        // Check if voucher is expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return [
                'success' => false,
                'error' => 'Voucher sudah kadaluarsa (expired: ' . $this->expires_at->format('d M Y H:i') . ')',
            ];
        }

        // Check if voucher has exceeded max uses
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return [
                'success' => false,
                'error' => 'Voucher sudah habis digunakan (maksimal ' . $this->max_uses . ' kali)',
            ];
        }

        // Check if user has already redeemed this voucher (1 user hanya bisa 1 kali)
        if ($this->isRedeemedBy($user)) {
            return [
                'success' => false,
                'error' => 'Anda sudah menggunakan voucher ini sebelumnya. Setiap user hanya bisa menggunakan 1 kode voucher 1 kali.',
            ];
        }

        // Use database transaction to ensure atomicity
        try {
            \DB::beginTransaction();

            // Double check to prevent race condition
            $existingRedemption = VoucherRedemption::where('voucher_id', $this->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existingRedemption) {
                \DB::rollBack();
                return [
                    'success' => false,
                    'error' => 'Anda sudah menggunakan voucher ini sebelumnya.',
                ];
            }

            // Create redemption record
            VoucherRedemption::create([
                'voucher_id' => $this->id,
                'user_id' => $user->id,
                'text_quota_received' => $this->text_quota,
                'multimedia_quota_received' => $this->multimedia_quota,
            ]);

            // Add quota to user
            $quota = UserQuota::getOrCreateForUser($user->id);
            if ($this->text_quota > 0) {
                $quota->addTextQuota($this->text_quota);
            }
            if ($this->multimedia_quota > 0) {
                $quota->addMultimediaQuota($this->multimedia_quota);
            }

            // Increment used count
            $this->increment('used_count');

            \DB::commit();

            return [
                'success' => true,
                'message' => 'Voucher berhasil digunakan!',
                'text_quota' => $this->text_quota,
                'multimedia_quota' => $this->multimedia_quota,
            ];
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Voucher redemption failed', [
                'voucher_id' => $this->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Terjadi kesalahan saat menggunakan voucher. Silakan coba lagi.',
            ];
        }
    }
}
