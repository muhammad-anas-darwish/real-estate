<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    // filterableColumns
    protected static array $defaultFilterableColumns = [];

    // multiFilterableColumns
    protected static array $defaultMultiFilterableColumns = ['id'];

    // searchableColumns
    protected static array $defaultSearchableColumns = [];

    // dateFilterableColumns
    protected static array $defaultDateFilterableColumns = ['created_at'];

    /**
     * Get filterable columns with fallback to defaults
     */
    protected static function getFilterableColumns(): array
    {
        return array_unique(
            property_exists(static::class, 'filterableColumns')
                ? static::$filterableColumns
                : static::$defaultFilterableColumns
        );
    }

    /**
     * Get multi-filterable columns with fallback to defaults
     */
    public static function getMultiFilterableColumns(): array
    {
        return array_unique(
            property_exists(static::class, 'multiFilterableColumns')
                ? static::$multiFilterableColumns
                : static::$defaultMultiFilterableColumns
        );
    }

    /**
     * Get searchable columns with fallback to defaults
     */
    public static function getSearchableColumns(): array
    {
        return array_unique(
            property_exists(static::class, 'searchableColumns')
                ? static::$searchableColumns
                : static::$defaultSearchableColumns
        );
    }

    /**
     * Get date filterable columns with fallback to defaults
     */
    public static function getDateFilterableColumns(): array
    {
        return array_unique(
            property_exists(static::class, 'dateFilterableColumns')
                ? static::$dateFilterableColumns
                : static::$defaultDateFilterableColumns
        );
    }

    public function scopeFilter(Builder $query): Builder
    {
        return $query
            ->when(request()->search, fn ($q) => $this->applySearchFilter($q))
            ->when($this->hasFilterableRequest(), fn ($q) => $this->applyColumnFilters($q))
            ->when($this->hasDateFilterableRequest(), fn ($q) => $this->applyDateFilters($q));
    }

    protected function applySearchFilter(Builder $query): Builder
    {
        return $query->where(function ($query) {
            foreach ($this->getSearchableColumns() as $column) {
                $query->orWhere(
                    $this->qualifyColumn($column),
                    'like',
                    '%'.request()->search.'%'
                );
            }
        });
    }

    protected function applyColumnFilters(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $this->applySingleValueFilters($query);
            $this->applyMultiValueFilters($query);
        });
    }

    protected function applySingleValueFilters(Builder $query): void
    {
        foreach ($this->getFilterableColumns() as $column) {
            if (request()->has($column)) {
                $query->where(
                    $this->qualifyColumn($column),
                    request($column)
                );
            }
        }
    }

    protected function applyMultiValueFilters(Builder $query): void
    {
        foreach ($this->getMultiFilterableColumns() as $column) {
            if (request()->has($column)) {
                $values = $this->normalizeFilterValues(request($column));
                $query->whereIn(
                    $this->qualifyColumn($column),
                    $values
                );
            }
        }
    }

    protected function normalizeFilterValues($values): array
    {
        return is_array($values)
            ? $values
            : explode(',', $values);
    }

    protected function hasFilterableRequest(): bool
    {
        $filterColumns = array_merge(
            $this->getFilterableColumns(),
            $this->getMultiFilterableColumns()
        );

        foreach ($filterColumns as $column) {
            if (request()->has($column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply date filters to the query
     */
    protected function applyDateFilters(Builder $query): Builder
    {
        return $query->where(function ($query) {
            foreach ($this->getDateFilterableColumns() as $column) {
                if (request()->has($column)) {
                    $this->applyDateFilter($query, $column);
                }
            }
        });
    }

    /**
     * Apply a single date filter
     */
    protected function applyDateFilter(Builder $query, string $column): void
    {
        $value = request($column);

        if (is_array($value)) {
            // Handle date range
            if (isset($value['from']) && isset($value['to'])) {
                $from = Carbon::parse($value['from'])->startOfDay();
                $to = Carbon::parse($value['to'])->endOfDay();
                $query->whereBetween($this->qualifyColumn($column), [$from, $to]);
            }
            // Handle single date in array format
            elseif (isset($value[0])) {
                $date = Carbon::parse($value[0]);
                $query->whereDate($this->qualifyColumn($column), $date);
            }
        }
        // Handle single date string
        elseif (is_string($value)) {
            $date = Carbon::parse($value);
            $query->whereDate($this->qualifyColumn($column), $date);
        }
    }

    /**
     * Check if any date filter is present in the request
     */
    protected function hasDateFilterableRequest(): bool
    {
        foreach ($this->getDateFilterableColumns() as $column) {
            if (request()->has($column)) {
                return true;
            }
        }

        return false;
    }
}
