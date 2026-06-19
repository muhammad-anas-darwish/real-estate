<?php

namespace Modules\Auth\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublisherUpgradeRequest extends BaseModel
{
    use HasFactory;

    protected $table = 'publisher_upgrade_requests';

    protected $fillable = [
        'user_id',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'status',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected static function newFactory()
    {
        return \Modules\Auth\Database\Factories\PublisherUpgradeRequestFactory::new();
    }
}
