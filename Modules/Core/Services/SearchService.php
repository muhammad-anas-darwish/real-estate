<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;

class SearchService
{
    public function searchCities(): \Illuminate\Database\Eloquent\Collection
    {
        return City::query()
            ->filter()
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function searchCountries(): \Illuminate\Database\Eloquent\Collection
    {
        return Country::query()
            ->filter()
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function searchUsers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->filter()
            ->limit(20)
            ->get(['id', 'name']);
    }

    public function call(string $type): \Illuminate\Support\Collection
    {
        $method = 'search' . Str::studly($type);

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return collect();
    }
}
