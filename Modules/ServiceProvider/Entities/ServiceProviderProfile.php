<?php

namespace Modules\ServiceProvider\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\ServiceProvider\Enums\PricingType;
use Modules\ServiceProvider\Enums\ServiceProviderType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServiceProviderProfile extends BaseModel implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $table = 'service_provider_profiles';

    protected $fillable = [
        'user_id',
        'type',
        'bio',
        'experience_years',
        'license_number',
        'price_type',
        'price_per_task',
        'is_verified',
        'is_available',
        'average_rating',
        'total_completed_tasks',
        'metadata',
    ];

    protected $casts = [
        'type' => ServiceProviderType::class,
        'price_type' => PricingType::class,
        'price_per_task' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_available' => 'boolean',
        'average_rating' => 'decimal:2',
        'experience_years' => 'integer',
        'total_completed_tasks' => 'integer',
        'metadata' => 'array',
    ];

    protected static $filterableColumns = [
        'type',
        'is_verified',
        'is_available',
        'price_type',
    ];

    protected static $searchableColumns = [
        'bio',
        'license_number',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('license_document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coverageAreas(): HasMany
    {
        return $this->hasMany(ServiceProviderCoverageArea::class);
    }

    public function getLicenseDocumentUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('license_document');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeInCity($query, int $cityId)
    {
        return $query->whereHas('coverageAreas', function ($q) use ($cityId) {
            $q->where('city_id', $cityId);
        });
    }
}
