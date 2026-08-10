<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\DTOs\CreateTextFileDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateTextFileDTO;
use Modules\FileSystem\Http\Requests\CreateTextFileRequest;
use Modules\FileSystem\Http\Requests\MoveItemRequest;
use Modules\FileSystem\Http\Requests\RenameItemRequest;
use Modules\FileSystem\Http\Requests\UpdateTextFileRequest;
use Modules\FileSystem\Http\Requests\UploadImageRequest;
use Modules\FileSystem\Services\FileService;
use Modules\FileSystem\Services\StorageQuotaService;

class FileController extends Controller
{
    public function __construct(
        private FileService $service,
    ) {}

    /**
     * عرض تفاصيل ملف واحد (يعمل للملفات النصية والصور)
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف الملف. Example: 1
     */
    public function show(int $id): JsonResponse
    {
        $file = $this->service->find($id);
        abort_if($file->user_id !== auth()->id(), 403, __('messages.unauthorized_access'));

        return $this->successResponse($file, 'File retrieved');
    }

    /**
     * إنشاء ملف نصي جديد
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @bodyParam folder_id integer required معرّف المجلد. Example: 3
     * @bodyParam name string required اسم الملف (مثل: "notes.txt"). Example: "ملاحظات.txt"
     * @bodyParam content string محتوى الملف النصي (اختياري). Example: "نص الملاحظة هنا"
     */
    public function storeText(CreateTextFileRequest $request): JsonResponse
    {
        $dto = CreateTextFileDTO::fromRequest($request->validated() + ['user_id' => auth()->id()]);
        $file = $this->service->createText($dto);

        return $this->successResponse($file, 'Text file created')->created('File');
    }

    /**
     * تعديل ملف نصي (اسم أو محتوى أو كلاهما)
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف الملف. Example: 1
     *
     * @bodyParam name string الاسم الجديد. Example: "notes-v2.txt"
     * @bodyParam content string المحتوى الجديد. Example: "محتوى محدّث"
     */
    public function updateText(UpdateTextFileRequest $request, int $id): JsonResponse
    {
        $dto = UpdateTextFileDTO::fromRequest($request->validated());
        $file = $this->service->updateText($id, $dto, auth()->id());

        return $this->successResponse($file, 'Text file updated')->updated('File');
    }

    /**
     * رفع صورة إلى مجلد
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @bodyParam folder_id integer required معرّف المجلد. Example: 3
     * @bodyParam image file required ملف الصورة (jpg, jpeg, png, webp, gif — أقصى 10 MB).
     * @bodyParam name string اسم الصورة (اختياري — يُستخدم اسم الملف الأصلي افتراضيًا). Example: "contract.jpg"
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        $image = $request->file('image');
        $name = $request->input('name', $image->getClientOriginalName());
        $file = $this->service->uploadImage(
            (int) $request->input('folder_id'),
            $image,
            $name,
            auth()->id()
        );

        $quotaService = app(StorageQuotaService::class);
        $quotaStatus = $quotaService->getStatus(auth()->id());

        return $this->successResponse([
            'file' => $file,
            'storage' => $quotaStatus,
        ], 'Image uploaded')->created('File');
    }

    /**
     * حذف ملف
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف الملف. Example: 1
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id, auth()->id());

        return $this->successResponse([], 'File deleted')->deleted('File');
    }

    /**
     * نقل ملف إلى مجلد آخر
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف الملف المُراد نقله. Example: 1
     *
     * @bodyParam target_folder_id integer required معرّف المجلد الوجهة. Example: 5
     */
    public function move(MoveItemRequest $request, int $id): JsonResponse
    {
        $dto = MoveItemDTO::fromRequest($request->validated());
        $file = $this->service->move($id, $dto, auth()->id());

        return $this->successResponse($file, 'File moved')->updated('File');
    }

    /**
     * إعادة تسمية ملف
     *
     * @group نظام الملفات
     *
     * @subgroup الملفات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف الملف. Example: 1
     *
     * @bodyParam name string required الاسم الجديد. Example: "new-name.txt"
     */
    public function rename(RenameItemRequest $request, int $id): JsonResponse
    {
        $dto = RenameItemDTO::fromRequest($request->validated());
        $file = $this->service->rename($id, $dto, auth()->id());

        return $this->successResponse($file, 'File renamed')->updated('File');
    }
}
