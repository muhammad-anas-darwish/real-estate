<?php

namespace Modules\RealEstate\Expert\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;
use Modules\Communication\Entities\ChatRoom;
use Modules\RealEstate\Database\Factories\ExpertRelationshipFactory;
use Modules\RealEstate\Expert\Enums\ExpertRelationshipStatus;

class ExpertRelationship extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): ExpertRelationshipFactory
    {
        return ExpertRelationshipFactory::new();
    }

    protected $table = 'expert_relationships';

    protected $fillable = [
        'expert_id',
        'user_id',
        'room_id',
        'status',
    ];

    protected $casts = [
        'status' => ExpertRelationshipStatus::class,
    ];

    protected static array $filterableColumns = [
        'status',
        'expert_id',
        'user_id',
    ];

    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ExpertRelationshipStatus::Active->value);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForExpert($query, int $expertId)
    {
        return $query->where('expert_id', $expertId);
    }
}
