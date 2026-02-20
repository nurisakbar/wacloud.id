<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
            
            // Generate referral code if not set
            if (empty($model->referral_code)) {
                $model->referral_code = static::generateReferralCode();
            }
        });
    }

    /**
     * Generate unique referral code
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('referral_code', $code)->exists());
        
        return $code;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'google_id',
        'auto_sync_incoming_messages',
        'role',
        'subscription_plan_id',
        'subscription_status',
        'trial_ends_at',
        'last_login_at',
        'referral_code',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'last_login_at' => 'datetime',
            'auto_sync_incoming_messages' => 'boolean',
        ];
    }

    /**
     * Get the user's subscription plan.
     */
    public function subscriptionPlan()
    {
        return $this->belongsTo(Plan::class, 'subscription_plan_id');
    }

    /**
     * Get the user's subscriptions.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's active subscription.
     */
    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    /**
     * Get the user's WhatsApp sessions.
     */
    public function whatsappSessions()
    {
        return $this->hasMany(WhatsAppSession::class);
    }

    /**
     * Get the user's messages.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the user's webhooks.
     */
    public function webhooks()
    {
        return $this->hasMany(Webhook::class);
    }

    /**
     * Get the user's API keys.
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the user's API usage logs.
     */
    public function apiUsageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Get the user's templates.
     */
    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the user's quota.
     */
    public function quota()
    {
        return $this->hasOne(UserQuota::class);
    }

    /**
     * Get the user's quota purchases.
     */
    public function quotaPurchases()
    {
        return $this->hasMany(QuotaPurchase::class);
    }

    /**
     * Get the user's voucher redemptions.
     */
    public function voucherRedemptions()
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    /**
     * Get or create user quota
     */
    public function getQuota()
    {
        return UserQuota::getOrCreateForUser($this->id);
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Get users referred by this user
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Get count of successful referrals
     */
    public function getReferralCountAttribute()
    {
        return $this->referrals()->count();
    }
}
