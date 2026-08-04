<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ResponseBuilder extends JsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [], $options = 0)
    {
        parent::__construct($data, $status, $headers, $options);
    }

    public function created(string $model): self
    {
        return $this->withMessage(
            __('crud.created', ['model' => __('models.'.$model)])
        )->withStatusCode(201);
    }

    public function updated(string $model): self
    {
        return $this->withMessage(
            __('crud.updated', ['model' => __('models.'.$model)])
        );
    }

    public function deleted(string $model): self
    {
        return $this->withMessage(
            __('crud.deleted', ['model' => __('models.'.$model)])
        );
    }

    public function withMessage(string $message): self
    {
        $data = $this->getData(true);
        $data['message'] = $message;
        $this->setData($data);

        return $this;
    }

    public function withStatusCode(int $code): self
    {
        $this->setStatusCode($code);

        return $this;
    }
}
