<?php

namespace Modules\ServiceProvider\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ServiceProvider\Enums\ServiceTaskType;

class ServiceRequestTask extends BaseModel
{
    protected $table = 'service_request_tasks';

    protected $fillable = [
        'service_request_id',
        'task_type',
        'checklist_json',
        'completed_at',
    ];

    protected $casts = [
        'task_type' => ServiceTaskType::class,
        'checklist_json' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static $filterableColumns = [
        'task_type',
        'service_request_id',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }
}
