<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Enums\StoragePackageType;

class StorageLimit extends BaseModel
{
    protected $table = 'storage_limits';

    protected $fillable = [
        'user_id', 'quota_bytes', 'used_bytes',
        'package_type', 'package_expires_at',
    ];

    protected $casts = [
        'quota_bytes' => 'integer',
        'used_bytes' => 'integer',
        'package_type' => StoragePackageType::class,
        'package_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUsedPercentageAttribute(): float
    {
        if ($this->quota_bytes <= 0) {
            return 100.0;
        }

        return round(($this->used_bytes / $this->quota_bytes) * 100, 2);
    }

    public function getRemainingBytesAttribute(): int
    {
        return max(0, $this->quota_bytes - $this->used_bytes);
    }

    public function isExceeded(): bool
    {
        return $this->used_bytes >= $this->quota_bytes;
    }

    public function isNearLimit(): bool
    {
        return $this->usedPercentage >= 80.0;
    }

    public function hasAvailableSpace(int $bytes): bool
    {
        return ($this->used_bytes + $bytes) <= $this->quota_bytes;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
