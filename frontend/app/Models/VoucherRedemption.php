<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VoucherRedemption extends Model
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
        'voucher_id',
        'user_id',
        'text_quota_received',
        'multimedia_quota_received',
    ];

    protected function casts(): array
    {
        return [
            'text_quota_received' => 'integer',
            'multimedia_quota_received' => 'integer',
        ];
    }

    /**
     * Get the voucher that was redeemed.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the user who redeemed the voucher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
