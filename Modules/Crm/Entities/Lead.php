<?php

namespace Modules\Crm\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\Crm\Enums\LeadSource;
use Modules\Crm\Enums\LeadStatus;
use Modules\RealEstate\Entities\Appointment;

class Lead extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'trader_id', 'name', 'phone', 'email',
        'source', 'status', 'lost_reason',
        'status_changed_at', 'archived_at', 'last_activity_at',
    ];

    protected $casts = [
        'source' => LeadSource::class,
        'status' => LeadStatus::class,
        'status_changed_at' => 'datetime',
        'archived_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected static $filterableColumns = ['status', 'source', 'trader_id'];

    protected static $searchableColumns = ['name', 'phone', 'email'];

    protected static $dateFilterableColumns = ['created_at', 'status_changed_at', 'last_activity_at'];

    public function trader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trader_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function followUps(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'followable');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeRecentlyActive($query)
    {
        return $query->orderByDesc('last_activity_at');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->trader_id === $userId;
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->saveQuietly();
    }

    protected static function newFactory()
    {
        return \Modules\Crm\Database\Factories\LeadFactory::new();
    }
}
