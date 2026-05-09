<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\DTOs\UserDTO;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\UserManagementService;

class UserController extends Controller
{
    public function __construct(protected readonly UserManagementService $userService)
    {
        $this->applyPermissions(
            'users',
            ['index', 'show', 'store', 'update', 'destroy'],
            additionalMethods: [
                'toggleStatus' => 'toggle-status',
            ],
        );
    }

    public function index()
    {
        $users = $this->userService->all();

        return $this->paginatedResponse(UserResource::collection($users));
    }

    public function show(int $id)
    {
        $user = $this->userService->find($id);

        return $this->successResponse(UserResource::make($user));
    }

    public function store(StoreUserRequest $request)
    {
        $dto = UserDTO::fromRequest($request->validated());
        $user = $this->userService->store($dto);

        return $this->successResponse(UserResource::make($user))->created('user');
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $dto = UserDTO::fromRequest($request->validated());
        $user = $this->userService->update($id, $dto);

        return $this->successResponse(UserResource::make($user))->updated('user');
    }

    public function destroy(int $id)
    {
        $this->userService->destroy($id);

        return $this->successResponse()->deleted('user');
    }

    public function toggleStatus(int $id)
    {
        $user = $this->userService->toggleStatus($id);

        return $this->successResponse(UserResource::make($user))->updated('user');
    }
}
