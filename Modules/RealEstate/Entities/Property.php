<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Property extends BaseModel implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'properties';

    protected $fillable = [
        'name',
        'description',
        'property_type',
        'type_of_contract',
        'country_id',
        'city_id',
        'type_of_contract',
        'property_type',
        'longitude',
        'latitude',
        'rooms',
        'bathrooms',
        'area',
        'detailed_info',
        'price',
        'currency',
        'publisher_id',
        'approved_by',
        'approved_at',
        'status',
    ];

    protected $casts = [
        'property_type' => PropertyType::class,
        'type_of_contract' => TypeOfContract::class,
        'longitude' => 'decimal:8',
        'latitude' => 'decimal:8',
        'rooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'decimal:2',
        'price' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'property_type',
        'type_of_contract',
        'country_id',
        'city_id',
        'status',
        'rooms',
        'bathrooms',
        'publisher_id',
    ];

    protected static $searchableColumns = [
        'name',
        'description',
    ];

    /**
     * Media collections for property images
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('main_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->sharpen(10);
    }

    /**
     * Get the publisher (user who created the listing)
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\SubModules\Location\Entities\Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\SubModules\Location\Entities\City::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function favoritedBy(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\Modules\Auth\Entities\User::class, 'property_user', 'property_id', 'user_id')->withTimestamps();
    }
    
    /**
     * Get the main image URL
     */
    public function getMainImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('main_image');
    }

    /**
     * Get the main image thumbnail URL
     */
    public function getMainImageThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('main_image', 'thumb');
    }

    /**
     * Get all gallery image URLs
     */
    public function getGalleryUrlsAttribute(): array
    {
        return $this->getMedia('gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
            ];
        })->toArray();
    }

    /**
     * Scope for approved properties
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending properties
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for filtering by price range
     */
    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope for filtering by area range
     */
    public function scopeAreaRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('area', '>=', $min);
        }
        if ($max !== null) {
            $query->where('area', '<=', $max);
        }
        return $query;
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\PropertyFactory::new();
    }
}
