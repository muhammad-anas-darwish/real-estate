<?php

namespace Modules\Core\TemporaryFile\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Core\TemporaryFile\DTOs\TemporaryFileDTO;
use Modules\Core\TemporaryFile\Http\Requests\UploadFileRequest;
use Modules\Core\TemporaryFile\Http\Resources\TemporaryFileResource;
use Modules\Core\TemporaryFile\Services\TemporaryFileService;

class UploadFileController extends Controller
{
    public function __construct(private readonly TemporaryFileService $TemporaryFileService)
    {
        //
    }

    public function store(UploadFileRequest $request)
    {
        try {

            $files = $this->TemporaryFileService->store(TemporaryFileDTO::fromRequest($request->validated()));

        } catch (Exception $exception) {

            Log::error($exception->getMessage());

            return $this->failedResponse($exception->getMessage());

        }

        return $this->successResponse(TemporaryFileResource::collection($files), message: __('Files uploaded successfully.'));
    }
}
