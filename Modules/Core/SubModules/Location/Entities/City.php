<?php

namespace Modules\Core\SubModules\Location\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends BaseModel
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'name',
        'country_id',
        'state_province',
        'postal_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static $filterableColumns = [
        'name',
        'country_code',
        'state_province',
        'postal_code',
        'is_active',
    ];

    protected static $searchableColumns = [
        'name',
    ];

    protected static function newFactory()
    {
        return \Modules\Core\Database\Factories\CityFactory::new();
    }
}
