<?php

namespace Modules\RealEstate\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Entities\Property;

class PropertyCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Property $property,
    ) {}
}
