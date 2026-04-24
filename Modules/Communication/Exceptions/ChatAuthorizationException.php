<?php

namespace Modules\Communication\Exceptions;

use Exception;

class ChatAuthorizationException extends Exception
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message);
    }
}