<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\DTOs\UpdateProfileDTO;
use Modules\Auth\Enums\ContactPreference;
use Modules\Auth\Http\Resources\OfficeProfileResource;
use Modules\Auth\Http\Resources\OfficeResource;
use Modules\Auth\Http\Resources\UpgradeRequestResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\PublisherService;
use Modules\Auth\Services\UpgradeRequestService;
use Modules\RealEstate\Services\AnalyticsService;
use Modules\Subscription\Services\SubscriptionAccess;

class PublisherController extends Controller
{
    public function __construct(
        protected readonly PublisherService $publisherService,
        protected readonly UpgradeRequestService $upgradeRequestService,
        protected readonly AnalyticsService $analyticsService
    ) {
        $this->applyPermissions(
            'offices',
            [],
            [
                'verify' => 'verify',
                'unverify' => 'unverify',
                'listUpgradeRequests' => 'list-upgrade-requests',
                'approveUpgrade' => 'approve-upgrade',
                'rejectUpgrade' => 'reject-upgrade',
            ]
        );
    }

    public function listOffices()
    {
        $offices = $this->publisherService->listOffices();

        return $this->paginatedResponse(OfficeResource::collection($offices));
    }

    public function showOffice($id)
    {
        $office = $this->publisherService->getOfficeProfile((int) $id);

        return $this->successResponse(OfficeProfileResource::make($office));
    }

    public function updateProfile()
    {
        $dto = UpdateProfileDTO::fromRequest(request()->all());
        $user = $this->publisherService->updateProfile(Auth::id(), $dto);

        return $this->successResponse(UserResource::make($user))->updated('profile');
    }

    public function updateContactPreference()
    {
        $preference = ContactPreference::from(request('contact_preference', 'chat'));
        $user = $this->publisherService->updateContactPreference(Auth::id(), $preference);

        return $this->successResponse(UserResource::make($user))->updated('contact_preference');
    }

    public function statistics()
    {
        $stats = $this->publisherService->getOfficeStatistics(Auth::id());

        return $this->successResponse($stats);
    }

    public function analytics()
    {
        $access = app(SubscriptionAccess::class);

        if (! $access->hasFeature(Auth::user(), 'advanced_analytics')) {
            return $this->failedResponse('Advanced analytics requires a subscription with this feature.', 403);
        }

        $overview = $this->analyticsService->getDashboardOverview(Auth::id());

        return $this->successResponse($overview);
    }

    public function submitUpgradeRequest()
    {
        $licenseDocument = request('license_document');
        $commercialRegisterDocument = request('commercial_register_document');

        $upgradeRequest = $this->upgradeRequestService->submitRequest(
            Auth::id(),
            $licenseDocument,
            $commercialRegisterDocument
        );

        return $this->successResponse(
            UpgradeRequestResource::make($upgradeRequest),
            'Upgrade request submitted successfully'
        );
    }

    public function upgradeStatus()
    {
        $request = $this->upgradeRequestService->getStatus(Auth::id());

        if (! $request) {
            return $this->successResponse(['has_request' => false, 'request' => null]);
        }

        return $this->successResponse([
            'has_request' => true,
            'request' => UpgradeRequestResource::make($request),
        ]);
    }

    public function verify($id)
    {
        $user = $this->publisherService->verify((int) $id);

        return $this->successResponse(UserResource::make($user), 'Office verified successfully');
    }

    public function unverify($id)
    {
        $user = $this->publisherService->unverify((int) $id);

        return $this->successResponse(UserResource::make($user), 'Office verification revoked');
    }

    public function listUpgradeRequests()
    {
        $requests = $this->upgradeRequestService->listPending();

        return $this->paginatedResponse(UpgradeRequestResource::collection($requests));
    }

    public function approveUpgrade($id)
    {
        $upgradeRequest = $this->upgradeRequestService->approve((int) $id, Auth::id());

        return $this->successResponse(
            UpgradeRequestResource::make($upgradeRequest),
            'Upgrade request approved'
        );
    }

    public function rejectUpgrade($id)
    {
        $reason = request('rejection_reason', '');
        $upgradeRequest = $this->upgradeRequestService->reject((int) $id, Auth::id(), $reason);

        return $this->successResponse(
            UpgradeRequestResource::make($upgradeRequest),
            'Upgrade request rejected'
        );
    }
}
