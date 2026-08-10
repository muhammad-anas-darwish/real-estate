<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ai\Services\DescriptionAssistantService;

class DescriptionAssistantController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly DescriptionAssistantService $assistant
    ) {
        $this->applyPermissions(
            'properties',
            [],
            [
                'generate' => 'create',
                'improve' => 'edit',
                'suggestTitle' => 'create',
                'suggestFeatures' => 'create',
            ]
        );
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request);

        $text = $this->assistant->generate($data);

        if ($text === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة حاليًا.', 503);
        }

        return $this->successResponse([
            'description' => trim($text),
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function improve(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, requireCurrent: true);

        $text = $this->assistant->improve($data);

        if ($text === null) {
            return $this->failedResponse('تعذّر التحسين. النص قصير جدًا أو الخدمة غير متاحة.', 422);
        }

        return $this->successResponse([
            'description' => trim($text),
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function suggestTitle(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request);

        $titles = $this->assistant->suggestTitles($data);

        if ($titles === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة.', 503);
        }

        return $this->successResponse([
            'titles' => $titles,
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function suggestFeatures(Request $request): JsonResponse
    {
        $request->validate([
            'property_type' => 'required|string',
            'language' => 'nullable|in:ar,en',
        ]);

        $features = $this->assistant->suggestFeatures($request->all());

        if ($features === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة.', 503);
        }

        return $this->successResponse([
            'features' => $features,
            'language' => $request->input('language', 'ar'),
        ]);
    }

    protected function validateRequest(Request $request, bool $requireCurrent = false): array
    {
        $rules = [
            'property_type' => 'nullable|string',
            'rooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area' => 'nullable|integer|min:0',
            'city' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:100',
            'current_description' => $requireCurrent ? 'required|string|min:10|max:2000' : 'nullable|string|max:2000',
            'language' => 'nullable|in:ar,en',
        ];

        return $this->validate($request, $rules);
    }
}
