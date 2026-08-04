<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'reviews';

    protected $fillable = [
        'reviewer_id',
        'reviewed_id',
        'property_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    protected static $filterableColumns = [
        'reviewed_id',
        'reviewer_id',
        'property_id',
        'rating',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class, 'reviewer_id');
    }

    public function reviewed(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class, 'reviewed_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\ReviewFactory::new();
    }
}
