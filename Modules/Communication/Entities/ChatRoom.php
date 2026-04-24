<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Communication\Enums\RoomTypeEnum;
use Modules\RealEstate\Entities\Property;

class ChatRoom extends BaseModel
{
    use HasFactory;

    protected $table = 'chat_rooms';

    protected $fillable = [
        'type',
        'name',
        'property_id',
    ];

    protected $casts = [
        'type' => RoomTypeEnum::class,
    ];

    protected static $filterableColumns = [
        'type',
        'property_id',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\Auth\Entities\User::class, 'chat_room_participants')
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeActive($query)
    {
        return $query->where('type', RoomTypeEnum::PRIVATE);
    }

    protected static function newFactory()
    {
        return \Modules\Communication\Database\Factories\ChatRoomFactory::new();
    }
}