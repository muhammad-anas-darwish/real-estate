<?php

namespace Modules\Crm\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;

class LeadNote extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['lead_id', 'author_id', 'body', 'is_locked'];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isEditableBy(User $user): bool
    {
        if ($this->author_id !== $user->id) {
            return false;
        }

        return $this->created_at->gt(now()->subHours(24));
    }

    public function isDeletableBy(User $user): bool
    {
        return $this->author_id === $user->id;
    }

    protected static function newFactory()
    {
        return \Modules\Crm\Database\Factories\LeadNoteFactory::new();
    }
}
