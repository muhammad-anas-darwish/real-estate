<?php

namespace Modules\ServiceProvider\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Entities\User;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\RealEstate\Entities\Review;
use Modules\ServiceProvider\DTOs\ServiceProviderProfileDTO;
use Modules\ServiceProvider\Entities\ServiceProviderCoverageArea;
use Modules\ServiceProvider\Entities\ServiceProviderProfile;
use Modules\ServiceProvider\Enums\ServiceProviderType;

class ServiceProviderService extends BaseService
{
    public const CACHE_TAG = 'service_providers';

    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function list(?string $type = null, ?int $cityId = null): LengthAwarePaginator
    {
        $query = ServiceProviderProfile::query()
            ->with(['user:id,name,email,phone,avatar_url', 'coverageAreas.city', 'media'])
            ->addSelect(['reviews_count' => Review::selectRaw('count(*)')
                ->whereColumn('reviewed_id', 'service_provider_profiles.user_id')
                ->limit(1),
            ])
            ->verified()
            ->available()
            ->filter()
            ->orderBy(request('sort_by', 'average_rating'), request('sort_order', 'desc'));

        if ($type) {
            $query->byType($type);
        }

        if ($cityId) {
            $query->inCity($cityId);
        }

        return $query->paginate($this->getPerPage());
    }

    public function find(int $id): ServiceProviderProfile
    {
        return ServiceProviderProfile::with([
            'user:id,name,email,phone,avatar_url',
            'coverageAreas.city',
            'media',
        ])
            ->addSelect(['reviews_count' => Review::selectRaw('count(*)')
                ->whereColumn('reviewed_id', 'service_provider_profiles.user_id')
                ->limit(1),
            ])
            ->findOrFail($id);
    }

    public function register(int $userId, ServiceProviderProfileDTO $dto): ServiceProviderProfile
    {
        return DB::transaction(function () use ($userId, $dto): ServiceProviderProfile {
            $user = User::findOrFail($userId);

            if ($user->serviceProviderProfile()->exists()) {
                throw new \RuntimeException('You already have a service provider profile.');
            }

            $profileData = $dto->toArray();
            if ($dto->type) {
                $profileData['type'] = ServiceProviderType::from($dto->type);
            }

            $profile = $user->serviceProviderProfile()->create($profileData);

            if ($dto->license_document) {
                $this->mediaSyncService->syncFiles(
                    model: $profile,
                    files: [$dto->license_document],
                    ruleName: 'property_images',
                    collectionName: 'license_document'
                );
            }

            if ($dto->coverage_city_ids) {
                foreach ($dto->coverage_city_ids as $cityId) {
                    ServiceProviderCoverageArea::create([
                        'service_provider_profile_id' => $profile->id,
                        'city_id' => $cityId,
                    ]);
                }
            }

            $user->update([
                'is_service_provider' => true,
                'service_provider_type' => $profile->type?->value,
            ]);

            return $profile->fresh()->load(['user:id,name,email,phone,avatar_url', 'coverageAreas.city', 'media']);
        });
    }

    public function update(int $id, ServiceProviderProfileDTO $dto): ServiceProviderProfile
    {
        return DB::transaction(function () use ($id, $dto): ServiceProviderProfile {
            $profile = ServiceProviderProfile::findOrFail($id);
            $profile->update($dto->toArray());

            if ($dto->license_document) {
                $this->mediaSyncService->syncFiles(
                    model: $profile,
                    files: [$dto->license_document],
                    ruleName: 'property_images',
                    collectionName: 'license_document'
                );
            }

            if ($dto->coverage_city_ids !== null) {
                $profile->coverageAreas()->delete();

                foreach ($dto->coverage_city_ids as $cityId) {
                    ServiceProviderCoverageArea::create([
                        'service_provider_profile_id' => $profile->id,
                        'city_id' => $cityId,
                    ]);
                }
            }

            if ($profile->wasChanged('type')) {
                $profile->user()->update([
                    'service_provider_type' => $profile->type?->value,
                ]);
            }

            return $profile->fresh()->load(['user:id,name,email,phone,avatar_url', 'coverageAreas.city', 'media']);
        });
    }

    public function toggleAvailability(int $id): ServiceProviderProfile
    {
        $profile = ServiceProviderProfile::findOrFail($id);
        $profile->is_available = ! $profile->is_available;
        $profile->save();

        return $profile->fresh();
    }

    public function verify(int $id): ServiceProviderProfile
    {
        return DB::transaction(function () use ($id): ServiceProviderProfile {
            $profile = ServiceProviderProfile::findOrFail($id);
            $profile->is_verified = true;
            $profile->save();

            return $profile->fresh()->load('user');
        });
    }

    public function unverify(int $id): ServiceProviderProfile
    {
        return DB::transaction(function () use ($id): ServiceProviderProfile {
            $profile = ServiceProviderProfile::findOrFail($id);
            $profile->is_verified = false;
            $profile->save();

            return $profile->fresh()->load('user');
        });
    }

    public function adminList(): LengthAwarePaginator
    {
        return ServiceProviderProfile::query()
            ->with(['user:id,name,email', 'coverageAreas.city', 'media'])
            ->filter()
            ->orderBy('created_at', 'desc')
            ->paginate($this->getPerPage());
    }
}
