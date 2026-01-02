<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuotaPurchase extends Model
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
            // Generate UUID if not set
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
            
            // Generate purchase number if not set
            if (empty($model->purchase_number)) {
                $model->purchase_number = 'QUOTA-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
            }
        });
    }

    protected $fillable = [
        'user_id',
        'purchase_number',
        'amount',
        'balance_added',
        'text_quota_added',
        'multimedia_quota_added',
        'payment_method',
        'status',
        'payment_reference',
        'payment_proof',
        'notes',
        'completed_at',
        'xendit_invoice_id',
        'xendit_invoice_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_added' => 'decimal:2',
            'text_quota_added' => 'integer',
            'multimedia_quota_added' => 'integer',
            'completed_at' => 'datetime',
        ];
    }


    /**
     * Get the user that made the purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark purchase as completed and add quota to user
     */
    public function complete(): void
    {
        if ($this->status === 'completed') {
            return;
        }

        $quota = UserQuota::getOrCreateForUser($this->user_id);
        
        // Add balance
        if ($this->balance_added > 0) {
            $quota->addBalance($this->balance_added);
        }
        
        // Add text quota
        if ($this->text_quota_added > 0) {
            $quota->addTextQuota($this->text_quota_added);
        }
        
        // Add multimedia quota
        if ($this->multimedia_quota_added > 0) {
            $quota->addMultimediaQuota($this->multimedia_quota_added);
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
