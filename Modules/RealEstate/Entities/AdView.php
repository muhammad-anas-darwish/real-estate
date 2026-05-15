<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;

class AdView extends BaseModel
{
    protected $table = 'ad_views';

    public $timestamps = false;

    protected $fillable = [
        'ad_id',
        'user_id',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
