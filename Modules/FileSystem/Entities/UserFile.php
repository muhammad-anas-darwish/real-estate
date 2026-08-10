<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Enums\FileType;

class UserFile extends BaseModel
{
    use HasFactory;

    protected $table = 'user_files';

    protected $fillable = [
        'user_id', 'folder_id', 'name', 'file_type',
        'mime_type', 'size', 'content', 'file_path',
    ];

    protected $casts = [
        'file_type' => FileType::class,
        'size' => 'integer',
    ];

    protected static $filterableColumns = [
        'user_id', 'folder_id', 'file_type',
    ];

    protected static $multiFilterableColumns = ['id', 'folder_id'];

    protected static $searchableColumns = ['name'];

    protected static $dateFilterableColumns = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(UserFolder::class, 'folder_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInFolder($query, int $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    public function scopeOfType($query, FileType $type)
    {
        return $query->where('file_type', $type);
    }

    public function isText(): bool
    {
        return $this->file_type === FileType::TEXT;
    }

    public function isImage(): bool
    {
        return $this->file_type === FileType::IMAGE;
    }

    protected static function newFactory()
    {
        return \Modules\FileSystem\Database\Factories\UserFileFactory::new();
    }
}
