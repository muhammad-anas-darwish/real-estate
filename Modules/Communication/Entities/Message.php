<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Communication\Enums\MessageTypeEnum;

class Message extends BaseModel
{
    use HasFactory;

    protected $table = 'messages';

    protected $fillable = [
        'room_id',
        'sender_id',
        'body',
        'type',
        'parent_id',
        'read_at',
        'attachment_path',
    ];

    protected $casts = [
        'type' => MessageTypeEnum::class,
        'read_at' => 'datetime',
    ];

    protected $hidden = [
        'pivot',
    ];

    protected static $filterableColumns = [
        'room_id',
        'sender_id',
        'type',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'room_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class, 'sender_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('sender_id', '!=', $userId)->whereNull('read_at');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        $disk = config('communication.chat.attachment_disk', 'local');

        if ($disk === 's3') {
            return \Illuminate\Support\Facades\Storage::disk($disk)->temporaryUrl(
                $this->attachment_path,
                now()->addMinutes(60)
            );
        }

        return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->attachment_path);
    }

    protected static function newFactory()
    {
        return \Modules\Communication\Database\Factories\MessageFactory::new();
    }
}