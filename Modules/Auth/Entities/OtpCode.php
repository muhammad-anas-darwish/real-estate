<?php

namespace Modules\Auth\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Enums\OtpPurpose;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property OtpPurpose $purpose
 * @property \Carbon\Carbon $expires_at
 * @property ?\Carbon\Carbon $verified_at
 */
class OtpCode extends BaseModel
{
    protected $fillable = [
        'user_id',
        'code',
        'purpose',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function markAsVerified(): void
    {
        $this->update(['verified_at' => now()]);
    }
}
