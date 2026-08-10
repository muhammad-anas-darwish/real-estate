<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\RentalCardStatus;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RentalCard extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $table = 'rental_cards';

    protected $fillable = [
        'property_id',
        'owner_id',
        'tenant_user_id',
        'external_tenant_name',
        'external_tenant_phone',
        'external_tenant_email',
        'external_tenant_id_notes',
        'start_date',
        'end_date',
        'terms',
        'notes',
        'is_renewable',
        'status',
        'ended_at',
        'end_reason',
        'ended_by',
        'renewed_at',
        'renewal_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_renewable' => 'boolean',
        'status' => RentalCardStatus::class,
        'ended_at' => 'datetime',
        'renewed_at' => 'datetime',
        'renewal_count' => 'integer',
    ];

    protected static $filterableColumns = [
        'property_id',
        'owner_id',
        'tenant_user_id',
        'status',
        'is_renewable',
    ];

    protected static $multiFilterableColumns = ['id', 'property_id'];

    protected static $searchableColumns = [
        'external_tenant_name',
        'external_tenant_phone',
        'external_tenant_email',
        'notes',
    ];

    protected static $dateFilterableColumns = [
        'start_date',
        'end_date',
        'created_at',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenantUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', RentalCardStatus::ACTIVE);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function getIsExternalTenantAttribute(): bool
    {
        return $this->tenant_user_id === null;
    }

    public function getTenantDisplayNameAttribute(): string
    {
        return $this->tenant_user_id
            ? $this->tenantUser?->name ?? '—'
            : $this->external_tenant_name ?? '—';
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== RentalCardStatus::ACTIVE) {
            return 0;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->end_date, false));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('pre_rental')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

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

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\RentalCardFactory::new();
    }
}
