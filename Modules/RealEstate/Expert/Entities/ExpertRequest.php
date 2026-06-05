<?php

namespace Modules\RealEstate\Expert\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Database\Factories\ExpertRequestFactory;
use Modules\RealEstate\Expert\Enums\ExpertRequestStatus;
use Modules\RealEstate\Expert\Enums\ExpertType;

class ExpertRequest extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): ExpertRequestFactory
    {
        return ExpertRequestFactory::new();
    }

    protected $fillable = [
        'user_id',
        'expert_type',
        'message',
        'status',
    ];

    protected $casts = [
        'expert_type' => ExpertType::class,
        'status' => ExpertRequestStatus::class,
    ];

    protected static array $filterableColumns = [
        'status',
        'expert_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relationship(): HasOne
    {
        return $this->hasOne(ExpertRelationship::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', ExpertRequestStatus::Pending->value);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', ExpertRequestStatus::Resolved->value);
    }
}
