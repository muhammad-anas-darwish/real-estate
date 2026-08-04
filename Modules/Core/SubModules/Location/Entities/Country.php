<?php

namespace Modules\Core\SubModules\Location\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends BaseModel
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'code',
        'phone_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static $filterableColumns = [
        'name',
        'code',
        'phone_code',
        'is_active',
    ];

    protected static $searchableColumns = [
        'name',
    ];

    protected static function newFactory()
    {
        return \Modules\Core\Database\Factories\CountryFactory::new();
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
