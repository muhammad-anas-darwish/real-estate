<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\JsonResponse;
use Throwable;

class CustomHandler // extends Handler
{
    use ApiResponses;

    public function __invoke(Throwable $e)
    {
        return $this->handleApiException($e);
    }

    protected function handleApiException(Throwable $exception): JsonResponse
    {
        if ($exception instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            return $exception->getResponse();
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->unauthorizedResponse();
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationErrorResponse(
                $exception->errors(),
                $exception->getMessage()
            );
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse();
        }

        return config('app.debug') ? response()->json([
            'message' => $exception->getMessage(),
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ], 500) : $this->serverErrorResponse();
    }
}
