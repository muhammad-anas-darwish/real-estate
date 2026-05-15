<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\AdMediaType;
use Modules\RealEstate\Enums\AdStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Ad extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    const CACHE_TAG = 'ads';

    protected $table = 'ads';

    protected $fillable = [
        'ad_group_id',
        'title',
        'description',
        'media_type',
        'external_url',
        'property_id',
        'status',
        'is_default',
        'start_date',
        'end_date',
        'created_by',
    ];

    protected $casts = [
        'media_type' => AdMediaType::class,
        'status' => AdStatus::class,
        'is_default' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected static $filterableColumns = [
        'status',
        'ad_group_id',
    ];

    protected static $searchableColumns = [
        'title',
    ];

    protected static $dateFilterableColumns = [
        'start_date',
        'end_date',
        'created_at',
    ];

    public function adGroup(): BelongsTo
    {
        return $this->belongsTo(AdGroup::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adMedia(): HasMany
    {
        return $this->hasMany(AdMedia::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', AdStatus::ACTIVE);
    }

    public function scopeScheduledForDate($query, string $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    public function scopeDefaultAd($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('status', '!=', AdStatus::ARCHIVED);
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\AdFactory::new();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('ad_media')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10)
            ->performOnCollections('ad_media');

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->sharpen(10)
            ->performOnCollections('ad_media');
    }
}
