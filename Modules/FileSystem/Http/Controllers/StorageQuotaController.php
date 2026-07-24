<?php

namespace Modules\FileSystem\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\FileSystem\Enums\StoragePackageType;
use Modules\FileSystem\Http\Requests\UpgradeStorageRequest;
use Modules\FileSystem\Services\StorageQuotaService;

class StorageQuotaController extends Controller
{
    public function __construct(
        private StorageQuotaService $service,
    ) {}

    /**
     * عرض حالة الحصة التخزينية للمستخدم الحالي
     *
     * @group نظام الملفات
     *
     * @subgroup المساحة التخزينية
     *
     * @authenticated
     *
     * @response {
     *   "data": {
     *     "quota_bytes": 104857600,
     *     "quota_readable": "100 MB",
     *     "used_bytes": 5000000,
     *     "used_readable": "4.77 MB",
     *     "remaining_bytes": 99857600,
     *     "used_percentage": 4.77,
     *     "package_type": "free",
     *     "is_exceeded": false,
     *     "is_near_limit": false,
     *     "can_upload": true,
     *     "available_packages": [...]
     *   }
     * }
     */
    public function status(): JsonResponse
    {
        $status = $this->service->getStatus(auth()->id());

        return $this->successResponse($status, 'Storage quota status');
    }

    /**
     * قائمة الباقات المتاحة للترقية (الأعلى من الحالية فقط)
     *
     * @group نظام الملفات
     *
     * @subgroup المساحة التخزينية
     *
     * @authenticated
     */
    public function packages(): JsonResponse
    {
        $limit = $this->service->getLimit(auth()->id());
        $packages = $this->service->getAvailablePackages($limit);

        return $this->successResponse(['packages' => $packages], 'Available packages');
    }

    /**
     * ترقية المساحة التخزينية إلى باقة جديدة
     *
     * @group نظام الملفات
     *
     * @subgroup المساحة التخزينية
     *
     * @authenticated
     *
     * @bodyParam package_type string required نوع الباقة (small, medium, large, max). Example: medium
     */
    public function upgrade(UpgradeStorageRequest $request): JsonResponse
    {
        $packageType = StoragePackageType::from($request->validated('package_type'));
        $limit = $this->service->upgrade(auth()->id(), $packageType);

        return $this->successResponse($limit, 'Storage upgraded')->updated('Storage');
    }
}
