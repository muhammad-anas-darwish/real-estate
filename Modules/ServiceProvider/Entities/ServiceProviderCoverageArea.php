<?php

namespace Modules\ServiceProvider\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProviderCoverageArea extends BaseModel
{
    protected $table = 'service_provider_coverage_areas';

    protected $fillable = [
        'service_provider_profile_id',
        'city_id',
    ];

    protected static $filterableColumns = [
        'service_provider_profile_id',
        'city_id',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class, 'service_provider_profile_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\SubModules\Location\Entities\City::class);
    }
}
