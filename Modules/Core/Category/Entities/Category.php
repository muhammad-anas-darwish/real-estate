<?php

namespace Modules\Core\Category\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    protected static $filterableColumns = [
        'type',
    ];

    protected static $searchableColumns = [
        'name',
    ];

    /**
     * Scope for filtering by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for property categories
     */
    public function scopeProperty($query)
    {
        return $query->where('type', 'property');
    }

    /**
     * Scope for car categories
     */
    public function scopeCar($query)
    {
        return $query->where('type', 'car');
    }

    protected static function newFactory()
    {
        return \Modules\Core\Category\Database\Factories\CategoryFactory::new();
    }
}
