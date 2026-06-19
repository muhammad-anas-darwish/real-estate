<?php

namespace Modules\Auth\Services;

use App\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Auth\DTOs\UpdateProfileDTO;
use Modules\Auth\Entities\User;
use Modules\Auth\Enums\ContactPreference;
use Modules\Auth\Enums\PublisherType;
use Modules\Core\TemporaryFile\Services\MediaSyncService;
use Modules\RealEstate\Entities\Property;

class PublisherService extends BaseService
{
    public const CACHE_TAG = 'publishers';

    public function __construct(
        protected MediaSyncService $mediaSyncService
    ) {}

    public function listOffices(): LengthAwarePaginator
    {
        return User::query()
            ->where('publisher_type', PublisherType::OFFICE->value)
            ->where('is_verified', true)
            ->withCount(['reviewsReceived as reviews_count'])
            ->with('media')
            ->filter()
            ->orderBy(request('sort_by', 'average_rating'), request('sort_order', 'desc'))
            ->paginate($this->getPerPage());
    }

    public function getOfficeProfile(int $userId): User
    {
        return User::where('publisher_type', PublisherType::OFFICE->value)
            ->where('is_verified', true)
            ->withCount(['reviewsReceived as reviews_count'])
            ->with('media')
            ->findOrFail($userId);
    }

    public function getOfficeStatistics(int $userId): array
    {
        return [
            'total_properties' => Property::where('publisher_id', $userId)->count(),
            'active_properties' => Property::where('publisher_id', $userId)->whereIn('status', ['pending', 'approved'])->count(),
            'sold_properties' => Property::where('publisher_id', $userId)->where('status', 'sold')->count(),
            'total_views' => (int) Property::where('publisher_id', $userId)->sum('views'),
            'reviews_count' => \Modules\RealEstate\Entities\Review::where('reviewed_id', $userId)->count(),
            'average_rating' => (float) User::find($userId)?->average_rating ?? 0,
        ];
    }

    public function updateProfile(int $userId, UpdateProfileDTO $dto): User
    {
        return DB::transaction(function () use ($userId, $dto): User {
            $user = User::findOrFail($userId);

            $data = array_filter([
                'name' => $dto->name,
                'phone' => $dto->phone,
                'website_url' => $dto->website_url,
                'social_links' => $dto->social_links,
                'description' => $dto->description,
                'employees_count' => $dto->employees_count,
            ], fn ($value) => $value !== null);

            $user->update($data);

            if ($dto->avatar) {
                $this->mediaSyncService->syncFiles(
                    model: $user,
                    files: [$dto->avatar],
                    ruleName: 'avatar',
                    collectionName: 'avatar'
                );
            }

            return $user->fresh()->load('media');
        });
    }

    public function updateContactPreference(int $userId, ContactPreference $preference): User
    {
        $user = User::findOrFail($userId);
        $user->contact_preference = $preference;
        $user->save();

        return $user;
    }

    public function verify(int $userId): User
    {
        return DB::transaction(function () use ($userId): User {
            $user = User::findOrFail($userId);
            $user->is_verified = true;
            $user->publisher_type = PublisherType::OFFICE->value;
            $user->save();

            return $user->fresh();
        });
    }

    public function unverify(int $userId): User
    {
        return DB::transaction(function () use ($userId): User {
            $user = User::findOrFail($userId);
            $user->is_verified = false;
            $user->save();

            return $user->fresh();
        });
    }
}
