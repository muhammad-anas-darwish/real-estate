<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends BaseModel
{
    use HasFactory;

    protected $table = 'user_notification_preferences';

    protected $fillable = [
        'user_id',
        'channel',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected static $filterableColumns = [
        'user_id',
        'channel',
        'enabled',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\Modules\Auth\Entities\User::class);
    }

    protected static function newFactory()
    {
        return \Modules\Communication\Database\Factories\UserNotificationPreferenceFactory::new();
    }
}
