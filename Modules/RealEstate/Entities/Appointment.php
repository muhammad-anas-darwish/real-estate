<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Entities\User;
use Modules\RealEstate\Enums\AppointmentType;
use Modules\RealEstate\Enums\ContactMethod;
use Modules\RealEstate\Enums\ViewingStatus;
use Modules\RealEstate\Enums\ViewingType;

class Appointment extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'property_viewings';

    protected $fillable = [
        'type',
        'property_id',
        'user_id',
        'agent_id',
        'scheduled_at',
        'duration_minutes',
        'buffer_minutes',
        'status',
        'viewing_type',
        'contact_method',
        'contact_name',
        'contact_phone',
        'notes',
        'agent_notes',
        'cancelled_by',
        'cancellation_reason',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'reminded_at',
        'max_attendees',
        'followable_type',
        'followable_id',
    ];

    protected $casts = [
        'type' => AppointmentType::class,
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'status' => ViewingStatus::class,
        'viewing_type' => ViewingType::class,
        'contact_method' => ContactMethod::class,
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminded_at' => 'datetime',
        'max_attendees' => 'integer',
    ];

    protected static $filterableColumns = [
        'type',
        'property_id',
        'user_id',
        'agent_id',
        'status',
        'viewing_type',
        'contact_method',
    ];

    protected static $multiFilterableColumns = ['id', 'property_id', 'user_id', 'agent_id', 'followable_id'];

    protected static $searchableColumns = [
        'contact_name',
        'contact_phone',
        'notes',
    ];

    protected static $dateFilterableColumns = [
        'scheduled_at',
        'created_at',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfType($query, string|AppointmentType $type)
    {
        $value = $type instanceof AppointmentType ? $type->value : $type;

        return $query->where('type', $value);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)->orWhere('agent_id', $userId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', ViewingStatus::PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', ViewingStatus::CONFIRMED);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>=', now())
            ->whereIn('status', [ViewingStatus::PENDING, ViewingStatus::CONFIRMED]);
    }

    public function scopeToday($query, ?int $agentId = null)
    {
        $query = $query->whereDate('scheduled_at', today());

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        return $query;
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeForProperty($query, int $propertyId)
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeNotCancelled($query)
    {
        return $query->whereNotIn('status', [ViewingStatus::CANCELLED, ViewingStatus::NO_SHOW]);
    }

    public function getEndsAtAttribute(): \Carbon\Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes + $this->buffer_minutes);
    }

    public function isOverlapping(\Carbon\Carbon $start, int $durationMinutes, int $bufferMinutes): bool
    {
        $newStart = $start->copy();
        $newEnd = $start->copy()->addMinutes($durationMinutes + $bufferMinutes);
        $existingStart = $this->scheduled_at->copy();
        $existingEnd = $this->ends_at->copy();

        return $newStart->lt($existingEnd) && $newEnd->gt($existingStart);
    }

    protected static function newFactory()
    {
        return \Modules\RealEstate\Database\Factories\AppointmentFactory::new();
    }
}
