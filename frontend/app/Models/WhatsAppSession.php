<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppSession extends Model
{
    protected $table = 'whatsapp_sessions';

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
            // Auto-generate UUID for session_id if not provided
            if (empty($model->session_id)) {
                $model->session_id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'session_name',
        'phone_number',
        'session_id',
        'status',
        'qr_code',
        'qr_code_expires_at',
        'device_info',
        'waha_instance_url',
        'last_activity_at',
        'connected_at',
        'disconnected_at',
    ];

    protected function casts(): array
    {
        return [
            'device_info' => 'array',
            'qr_code_expires_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for this session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'session_id');
    }

    /**
     * Get the webhooks for this session.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class, 'session_id');
    }

    /**
     * Check if session is connected.
     */
    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    /**
     * Check if session is pairing.
     */
    public function isPairing(): bool
    {
        return $this->status === 'pairing';
    }

    /**
     * Check if QR code is expired.
     */
    public function isQrCodeExpired(): bool
    {
        return $this->qr_code_expires_at && $this->qr_code_expires_at->isPast();
    }
}
