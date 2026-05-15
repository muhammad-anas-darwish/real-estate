<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;

class AdGroup extends BaseModel
{
    use HasFactory, SoftDeletes;

    const CACHE_TAG = 'ad-groups';

    protected $table = 'ad_groups';

    protected $fillable = [
        'name',
        'description',
        'status',
        'is_archived',
        'created_by',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    protected static $filterableColumns = [
        'status',
    ];

    protected static $searchableColumns = [
        'name',
        'description',
    ];

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\AdGroupFactory::new();
    }
}
