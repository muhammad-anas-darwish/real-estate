<?php

namespace Modules\Ledger\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class AccountResource extends BaseJsonResource
{
    protected bool $withChildren = false;

    public function withChildren(): static
    {
        $this->withChildren = true;

        return $this;
    }

    protected function getCustomData(): array
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'account_number' => $this->account_number,
            'account_category' => $this->account_category?->value,
            'account_category_label' => $this->account_category?->label(),
            'account_category_label_ar' => $this->account_category?->labelAr(),
            'normal_balance' => $this->account_category?->normalBalance(),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'description' => $this->description,
            'currency' => $this->currency,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'current_balance' => (float) $this->current_balance,
            'held_balance' => (float) $this->held_balance,
            'available_balance' => (float) $this->available_balance,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];

        if ($this->withChildren && $this->relationLoaded('children')) {
            $data['children'] = AccountResource::collection(
                $this->children->sortBy('sort_order')
            );
        }

        return $data;
    }

    protected function getRelationMap(): array
    {
        return [
            'parent' => AccountResource::class,
            'entries' => AccountEntryResource::class,
        ];
    }
}
