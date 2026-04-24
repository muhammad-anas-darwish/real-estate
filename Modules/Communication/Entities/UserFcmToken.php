<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Communication\Enums\DeviceTypeEnum;

class UserFcmToken extends BaseModel
{
    use HasFactory;

    protected $table = 'user_fcm_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'last_used_at',
    ];

    protected $casts = [
        'device_type' => DeviceTypeEnum::class,
        'last_used_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'user_id',
        'device_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('last_used_at', '>=', now()->subDays(30));
    }

    protected static function newFactory()
    {
        return \Modules\Communication\Database\Factories\UserFcmTokenFactory::new();
    }
}