<?php

namespace Modules\Communication\Exceptions;

use Exception;
use Illuminate\Http\Request;

class ChatAuthorizationException extends Exception
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message);
    }

    public function render(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => null,
        ], 403);
    }
}
