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

        if ($exception instanceof \Modules\Communication\Exceptions\ChatAuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
            ], 403);
        }

        $message = mb_convert_encoding($exception->getMessage(), 'UTF-8', 'UTF-8');

        return config('app.debug') ? response()->json([
            'message' => $message,
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ], 500) : $this->serverErrorResponse();
    }
}
