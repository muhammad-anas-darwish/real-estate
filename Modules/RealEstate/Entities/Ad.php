<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\Ledger\Entities\AccountEntry;
use Modules\RealEstate\Enums\AdMediaType;
use Modules\RealEstate\Enums\AdStatus;
use Modules\RealEstate\Enums\AdType;
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
        'type',
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
        'user_id',
        'amount_paid',
        'currency',
        'payment_method',
        'payment_reference',
        'pricing_tier',
        'sponsor_duration',
        'target_url',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'type' => AdType::class,
        'media_type' => AdMediaType::class,
        'status' => AdStatus::class,
        'is_default' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'status',
        'ad_group_id',
        'type',
        'user_id',
        'pricing_tier',
    ];

    protected static $searchableColumns = [
        'title',
    ];

    protected static $dateFilterableColumns = [
        'start_date',
        'end_date',
        'starts_at',
        'ends_at',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adMedia(): HasMany
    {
        return $this->hasMany(AdMedia::class);
    }

    public function ledgerEntries()
    {
        return AccountEntry::where('reference_type', 'ad_payment')
            ->where('reference_id', $this->id);
    }

    public function scopeOfType($query, AdType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBanner($query)
    {
        return $query->where('type', AdType::BANNER);
    }

    public function scopeSponsored($query)
    {
        return $query->where('type', AdType::SPONSORED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', AdStatus::ACTIVE);
    }

    public function scopeActiveSponsored($query)
    {
        return $query->where('type', AdType::SPONSORED)
            ->where('status', AdStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
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

    public function scopeHighestRotationWeight($query)
    {
        $weights = [
            'premium' => 4,
            'standard' => 2,
            'basic' => 1,
        ];

        $cases = [];
        foreach ($weights as $tier => $weight) {
            $cases[] = "WHEN '{$tier}' THEN {$weight}";
        }
        $caseSql = implode(' ', $cases);

        return $query->orderByRaw("CASE pricing_tier {$caseSql} ELSE 0 END DESC");
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
