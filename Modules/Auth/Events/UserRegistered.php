<?php

namespace Modules\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Entities\User;

class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}
}
