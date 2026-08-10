<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\DTOs\CreateFolderDTO;
use Modules\FileSystem\DTOs\MoveItemDTO;
use Modules\FileSystem\DTOs\RenameItemDTO;
use Modules\FileSystem\DTOs\UpdateFolderDTO;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Http\Requests\CreateFolderRequest;
use Modules\FileSystem\Http\Requests\MoveItemRequest;
use Modules\FileSystem\Http\Requests\RenameItemRequest;
use Modules\FileSystem\Http\Requests\UpdateFolderRequest;
use Modules\FileSystem\Services\FolderService;
use Modules\FileSystem\Services\StorageQuotaService;

class FolderController extends Controller
{
    public function __construct(
        private FolderService $service,
    ) {}

    /**
     * قائمة المجلدات في مستوى معيّن (الجذر افتراضيًا)
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @queryParam parent_id integer معرّف المجلد الأب (null للجذر). Example: 5
     *
     * @response {
     *   "data": [...],
     *   "message": "Folders retrieved"
     * }
     */
    public function index(): JsonResponse
    {
        $folders = $this->service->listRoot(
            auth()->id(),
            request('parent_id')
        );

        return $this->paginatedResponse($folders, 'Folders retrieved');
    }

    /**
     * عرض محتويات مجلد (مجلدات فرعية + ملفات + مسار تنقّل)
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف المجلد. Example: 1
     *
     * @response {
     *   "data": {
     *     "folder": {...},
     *     "children": [...],
     *     "files": [...],
     *     "breadcrumbs": [...],
     *     "storage": {...}
     *   }
     * }
     */
    public function show(int $id): JsonResponse
    {
        $contents = $this->service->contents($id, auth()->id());
        $quotaService = app(StorageQuotaService::class);
        $contents['storage'] = $quotaService->getStatus(auth()->id());

        return $this->successResponse($contents, 'Folder contents retrieved');
    }

    /**
     * إنشاء مجلد جديد داخل مجلد أب (أو في الجذر)
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @bodyParam parent_id integer معرّف المجلد الأب (اختياري، null = الجذر). Example: 3
     * @bodyParam name string required اسم المجلد. Example: "مستندات العقار"
     *
     * @response 201 {
     *   "data": {...},
     *   "message": "Folder created"
     * }
     */
    public function store(CreateFolderRequest $request): JsonResponse
    {
        $dto = CreateFolderDTO::fromRequest($request->validated() + ['user_id' => auth()->id()]);
        $folder = $this->service->create($dto);

        return $this->successResponse($folder, 'Folder created')->created('Folder');
    }

    /**
     * تحديث اسم المجلد
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف المجلد. Example: 1
     *
     * @bodyParam name string required الاسم الجديد. Example: "اسم محدّث"
     */
    public function update(UpdateFolderRequest $request, int $id): JsonResponse
    {
        $dto = UpdateFolderDTO::fromRequest($request->validated());
        $folder = $this->service->update($id, $dto, auth()->id());

        return $this->successResponse($folder, 'Folder updated')->updated('Folder');
    }

    /**
     * حذف مجلد فارغ (غير محمي)
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف المجلد. Example: 1
     *
     * @response 403 "المجلد محمي أو غير فارغ"
     */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id, auth()->id());

        return $this->successResponse([], 'Folder deleted')->deleted('Folder');
    }

    /**
     * نقل مجلد إلى مجلد آخر
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف المجلد المُراد نقله. Example: 1
     *
     * @bodyParam target_folder_id integer required معرّف المجلد الوجهة. Example: 5
     */
    public function move(MoveItemRequest $request, int $id): JsonResponse
    {
        $dto = MoveItemDTO::fromRequest($request->validated());
        $folder = $this->service->move($id, $dto, auth()->id());

        return $this->successResponse($folder, 'Folder moved')->updated('Folder');
    }

    /**
     * إعادة تسمية مجلد
     *
     * @group نظام الملفات
     *
     * @subgroup المجلدات
     *
     * @authenticated
     *
     * @urlParam id integer required معرّف المجلد. Example: 1
     *
     * @bodyParam name string required الاسم الجديد. Example: "اسم جديد"
     */
    public function rename(RenameItemRequest $request, int $id): JsonResponse
    {
        $dto = RenameItemDTO::fromRequest($request->validated());
        $folder = $this->service->rename($id, $dto, auth()->id());

        return $this->successResponse($folder, 'Folder renamed')->updated('Folder');
    }

    public function propertyFolder(int $propertyId): JsonResponse
    {
        $folder = UserFolder::where('source_type', 'Property')
            ->where('source_id', $propertyId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return $this->show($folder->id);
    }
}
