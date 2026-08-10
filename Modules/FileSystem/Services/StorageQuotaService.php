<?php

namespace Modules\FileSystem\Services;

use App\Services\BaseService;
use Modules\FileSystem\Entities\StorageLimit;
use Modules\FileSystem\Enums\StoragePackageType;

class StorageQuotaService extends BaseService
{
    protected const CACHE_TAG = 'storage_quota';

    public function getLimit(int $userId): StorageLimit
    {
        return StorageLimit::forUser($userId)->firstOrFail();
    }

    public function getStatus(int $userId): array
    {
        $limit = $this->getLimit($userId);

        return [
            'user_id' => $limit->user_id,
            'quota_bytes' => $limit->quota_bytes,
            'quota_readable' => $this->formatBytes($limit->quota_bytes),
            'used_bytes' => $limit->used_bytes,
            'used_readable' => $this->formatBytes($limit->used_bytes),
            'remaining_bytes' => $limit->remaining_bytes,
            'remaining_readable' => $this->formatBytes($limit->remaining_bytes),
            'used_percentage' => $limit->used_percentage,
            'package_type' => $limit->package_type?->value,
            'package_expires_at' => $limit->package_expires_at?->format('Y-m-d H:i:s'),
            'is_exceeded' => $limit->isExceeded(),
            'is_near_limit' => $limit->isNearLimit(),
            'can_upload' => ! $limit->isExceeded(),
            'available_packages' => $this->getAvailablePackages($limit),
        ];
    }

    public function getAvailablePackages(StorageLimit $limit): array
    {
        $current = $limit->package_type;
        $packages = [];

        foreach (StoragePackageType::cases() as $type) {
            if ($type->quotaBytes() <= $current->quotaBytes()) {
                continue;
            }
            $packages[] = [
                'type' => $type->value,
                'label' => $type->label(),
                'quota_bytes' => $type->quotaBytes(),
                'quota_readable' => $this->formatBytes($type->quotaBytes()),
            ];
        }

        return $packages;
    }

    public function upgrade(int $userId, StoragePackageType $newPackage): StorageLimit
    {
        $limit = $this->getLimit($userId);

        if ($newPackage->quotaBytes() <= $limit->package_type->quotaBytes()) {
            throw new \InvalidArgumentException(__('messages.package_must_be_higher'));
        }

        if ($newPackage === StoragePackageType::MAX && $limit->quota_bytes >= StoragePackageType::MAX->quotaBytes()) {
            throw new \InvalidArgumentException(__('messages.max_storage_reached'));
        }

        $limit->update([
            'quota_bytes' => $newPackage->quotaBytes(),
            'package_type' => $newPackage,
        ]);

        $this->clearCache();

        return $limit->fresh();
    }

    public function resetToFree(int $userId): StorageLimit
    {
        $limit = $this->getLimit($userId);
        $freeType = StoragePackageType::FREE;

        $limit->update([
            'quota_bytes' => $freeType->quotaBytes(),
            'package_type' => $freeType,
            'package_expires_at' => null,
        ]);

        $this->clearCache();

        return $limit->fresh();
    }

    public function expireStalePackages(): int
    {
        $expired = StorageLimit::where('package_expires_at', '<', now())
            ->where('package_type', '!=', StoragePackageType::FREE)
            ->get();

        $count = 0;
        foreach ($expired as $limit) {
            $this->resetToFree($limit->user_id);
            $count++;
        }

        return $count;
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision).' '.$units[$pow];
    }
}
