<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Request;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\PropertyDirection;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\RentalCardStatus;
use Modules\RealEstate\Enums\TypeOfContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Property extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
        'home_directions',
        'detailed_info',
        'price',
        'currency',
        'publisher_id',
        'publisher_type',
        'approved_by',
        'approved_at',
        'status',
        'rejection_reason',
        'views',
        'is_physically_verified',
        'inspection_requested_at',
        'inspection_completed_at',
        'inspection_score',
        'inspection_report',
    ];

    protected $casts = [
        'property_type' => PropertyType::class,
        'type_of_contract' => TypeOfContract::class,
        'status' => PropertyStatus::class,
        'longitude' => 'decimal:8',
        'latitude' => 'decimal:8',
        'rooms' => 'integer',
        'bathrooms' => 'integer',
        'area' => 'decimal:2',
        'home_directions' => PropertyDirection::class,
        'price' => 'decimal:2',
        'views' => 'integer',
        'approved_at' => 'datetime',
        'is_physically_verified' => 'boolean',
        'inspection_requested_at' => 'datetime',
        'inspection_completed_at' => 'datetime',
        'inspection_score' => 'integer',
        'inspection_report' => 'array',
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
        'publisher_type',
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
    public function registerMediaConversions(?Media $media = null): void
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

    public function ads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ad::class);
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
     * Scope for filtering by publisher (user who created the listing)
     */
    public function scopeByPublisher($query, int $userId)
    {
        return $query->where('publisher_id', $userId);
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

    /**
     * Scope for filtering by rooms range
     */
    public function scopeRoomsRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('rooms', '>=', $min);
        }
        if ($max !== null) {
            $query->where('rooms', '<=', $max);
        }

        return $query;
    }

    /**
     * Scope for filtering by bathrooms range
     */
    public function scopeBathroomsRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('bathrooms', '>=', $min);
        }
        if ($max !== null) {
            $query->where('bathrooms', '<=', $max);
        }

        return $query;
    }

    /**
     * Relationship to track property views
     */
    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PropertyView::class);
    }

    /**
     * Increment view counter and record the visit
     */
    public function incrementViews(): void
    {
        $this->increment('views');

        $this->views()->create([
            'user_id' => auth()->id(),
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Scope for most viewed properties
     */
    public function scopeMostViewed($query, int $limit = 10)
    {
        return $query->orderBy('views', 'desc')->limit($limit);
    }

    public function scopeUnderInspection($query)
    {
        return $query->where('status', PropertyStatus::UNDER_INSPECTION);
    }

    public function scopePhysicallyVerified($query)
    {
        return $query->where('is_physically_verified', true);
    }

    public function serviceRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\ServiceProvider\Entities\ServiceRequest::class, 'property_id');
    }

    public function rentalCards(): HasMany
    {
        return $this->hasMany(RentalCard::class);
    }

    public function activeRentalCard(): HasOne
    {
        return $this->hasOne(RentalCard::class)->where('status', RentalCardStatus::ACTIVE);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Property $property) {
            if ($property->isForceDeleting()) {
                return;
            }

            $property->rentalCards()->delete();
        });
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\PropertyFactory::new();
    }
}
