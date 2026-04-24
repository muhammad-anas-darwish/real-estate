<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Entities\User;
use Modules\Communication\Enums\ConversationType;

class Conversation extends BaseModel
{
    use HasFactory;

    protected $table = 'conversations';

    protected $fillable = [
        'property_id',
        'type',
        'initiator_id',
        'recipient_id',
    ];

    protected $casts = [
        'type' => ConversationType::class,
    ];

    protected static $filterableColumns = [
        'type',
        'initiator_id',
        'recipient_id',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(\Modules\RealEstate\Entities\Property::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    protected static function newFactory()
    {
        return \Modules\Communication\Database\Factories\ConversationFactory::new();
    }
}