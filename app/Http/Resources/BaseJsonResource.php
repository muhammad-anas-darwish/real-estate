<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseJsonResource extends JsonResource
{
    /**
     * Default date format for the application
     */
    protected string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Base fields that are automatically included
     */
    protected array $baseFields = ['id', 'created_at', 'updated_at'];

    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return array_merge(
            $this->getBaseFields(),
            $this->getCustomData(),
            $this->getIncludedRelations()
        );
    }

    /**
     * Get the base fields that should always be included
     */
    protected function getBaseFields(): array
    {
        $fields = [];

        if (in_array('id', $this->baseFields)) {
            $fields['id'] = $this->id;
        }

        if (in_array('created_at', $this->baseFields) && isset($this->created_at)) {
            $fields['created_at'] = $this->formatDate($this->created_at);
        }

        if (in_array('updated_at', $this->baseFields) && isset($this->updated_at)) {
            $fields['updated_at'] = $this->formatDate($this->updated_at);
        }

        return $fields;
    }

    /**
     * Format a date consistently
     */
    public function formatDate($date): ?string
    {
        return $date ? $date->format($this->dateFormat) : null;
    }

    /**
     * Get the included relationships
     */
    protected function getIncludedRelations(): array
    {
        $relations = [];

        foreach ($this->getRelationMap() as $relation => $resourceClass) {
            if ($this->relationLoaded($relation)) {
                $relations[$relation] = $this->formatRelation(
                    $this->$relation,
                    $resourceClass
                );
            }
        }

        return $relations;
    }

    /**
     * Format a relationship for the response
     */
    protected function formatRelation($relation, string $resourceClass)
    {
        if (is_null($relation)) {
            return null;
        }

        return is_iterable($relation)
            ? $resourceClass::collection($relation)
            : new $resourceClass($relation);
    }

    /**
     * Relationship map - defines which relations use which resources
     *
     * @return array [relation_name => Resource::class]
     */
    protected function getRelationMap(): array
    {
        return [];
    }

    /**
     * Custom data specific to each resource
     *
     * @return array The resource-specific data
     */
    abstract protected function getCustomData(): array;
}
