<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use Filterable;

    /**
     * The "booted" method of the model.
     * Using protected and final for better encapsulation and to prevent override
     */
    final protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->creatingModel();
        });

        static::created(function (self $model) {
            $model->createdModel();
        });

        static::updating(function (self $model) {
            $model->updatingModel();
        });

        static::updated(function (self $model) {
            $model->updatedModel();
        });

        static::deleting(function (self $model) {
            $model->deletingModel();
        });

        static::deleted(function (self $model) {
            $model->deletedModel();
        });
    }

    /**
     * Model lifecycle hook methods
     * Made empty by default to avoid unnecessary method calls
     * Marked as protected to follow better encapsulation
     */
    protected function createdModel(): void
    {
        // Intentionally left empty
    }

    protected function creatingModel(): void
    {
        // Intentionally left empty
    }

    protected function updatedModel(): void
    {
        // Intentionally left empty
    }

    protected function updatingModel(): void
    {
        // Intentionally left empty
    }

    protected function deletingModel(): void
    {
        // Intentionally left empty
    }

    protected function deletedModel(): void
    {
        // Intentionally left empty
    }
}
