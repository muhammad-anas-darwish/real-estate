<?php

namespace Modules\Auth\Entities;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Communication\Entities\HasFcmTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Filterable, HasApiTokens, HasFactory, HasFcmTokens, HasRoles, Notifiable;

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
    ];

    protected static $filterableColumns = [
        'status',
    ];

    protected static $searchableColumns = [
        'name',
        'email',
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
        ];
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
