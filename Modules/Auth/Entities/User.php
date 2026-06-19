<?php

namespace Modules\Auth\Entities;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Enums\ContactPreference;
use Modules\Auth\Enums\PublisherType;
use Modules\Communication\Entities\HasFcmTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use Filterable, HasApiTokens, HasFactory, HasFcmTokens, HasRoles, InteractsWithMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'publisher_type',
        'phone',
        'website_url',
        'social_links',
        'description',
        'is_verified',
        'employees_count',
        'contact_preference',
        'average_rating',
    ];

    protected static $filterableColumns = [
        'status',
        'publisher_type',
        'is_verified',
    ];

    protected static $searchableColumns = [
        'name',
        'email',
        'description',
        'phone',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('license_document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);

        $this->addMediaCollection('commercial_register_document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    public function lovedProperties(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\RealEstate\Entities\Property::class, 'property_user', 'user_id', 'property_id')->withTimestamps();
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Subscription\Entities\Subscription::class);
    }

    public function activeSubscription(): ?\Modules\Subscription\Entities\Subscription
    {
        return $this->subscriptions()->where('status', 'active')->latest('ends_at')->first();
    }

    public function ledgerAccount(): ?\Modules\Ledger\Entities\Account
    {
        return $this->morphOne(\Modules\Ledger\Entities\Account::class, 'owner')
            ->where('type', \Modules\Ledger\Enums\AccountType::USER_BALANCE);
    }

    public function balance(): float
    {
        return (float) ($this->ledgerAccount?->available_balance ?? 0);
    }

    public function reviewsReceived(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\RealEstate\Entities\Review::class, 'reviewed_id');
    }

    public function reviewsGiven(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\RealEstate\Entities\Review::class, 'reviewer_id');
    }

    public function upgradeRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PublisherUpgradeRequest::class);
    }

    public function scopeOffices($query)
    {
        return $query->where('publisher_type', PublisherType::OFFICE->value);
    }

    public function scopeIndividuals($query)
    {
        return $query->where('publisher_type', PublisherType::INDIVIDUAL->value);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

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
            'status' => 'string',
            'publisher_type' => PublisherType::class,
            'social_links' => 'array',
            'is_verified' => 'boolean',
            'contact_preference' => ContactPreference::class,
            'average_rating' => 'decimal:2',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar');
    }

    public function getLicenseDocumentUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('license_document');
    }

    public function getCommercialRegisterDocumentUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('commercial_register_document');
    }

    protected static function newFactory()
    {
        return \Modules\Auth\Database\Factories\UserFactory::new();
    }

    // public function sendPasswordResetNotification($token)
    // {
    // Generate your frontend reset password URL
    // $url = config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);

    // $this->notify(new ResetPasswordNotification($token, $url));
    // }
}
