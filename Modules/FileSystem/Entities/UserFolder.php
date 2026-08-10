<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Entities\User;

class UserFolder extends BaseModel
{
    use HasFactory;

    protected $table = 'user_folders';

    protected $fillable = [
        'user_id', 'parent_id', 'name', 'folder_type',
        'source_id', 'source_type', 'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'source_id' => 'integer',
    ];

    protected static $filterableColumns = [
        'user_id', 'parent_id', 'folder_type', 'is_protected',
    ];

    protected static $multiFilterableColumns = ['id', 'user_id'];

    protected static $searchableColumns = ['name'];

    protected static $dateFilterableColumns = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(UserFile::class, 'folder_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeProtected($query)
    {
        return $query->where('is_protected', true);
    }

    public function isPropertyFolder(): bool
    {
        return $this->folder_type === 'property';
    }

    public function isGeneralFolder(): bool
    {
        return $this->folder_type === 'general';
    }

    public function isMovable(): bool
    {
        return ! $this->is_protected;
    }

    public function isDescendantOf(self $potentialParent): bool
    {
        $current = $this->parent;

        while ($current) {
            if ($current->id === $potentialParent->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    protected static function newFactory()
    {
        return \Modules\FileSystem\Database\Factories\UserFolderFactory::new();
    }
}
