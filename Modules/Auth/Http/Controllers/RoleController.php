<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\DTOs\RoleDTO;
use Modules\Auth\Http\Requests\AssignPermissionsRequest;
use Modules\Auth\Http\Requests\StoreRoleRequest;
use Modules\Auth\Http\Requests\UpdateRoleRequest;
use Modules\Auth\Http\Resources\PermissionResource;
use Modules\Auth\Http\Resources\RoleResource;
use Modules\Auth\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(protected readonly RoleService $roleService)
    {
        $this->applyPermissions(
            'roles',
            ['index', 'show', 'store', 'update', 'destroy'],
            additionalMethods: [
                'assignPermissions' => 'edit',
                'permissions' => 'list',
            ],
        );
    }

    public function index()
    {
        $roles = $this->roleService->all();

        return $this->paginatedResponse(RoleResource::collection($roles));
    }

    public function show(int $id)
    {
        $role = $this->roleService->find($id);

        return $this->successResponse(RoleResource::make($role));
    }

    public function store(StoreRoleRequest $request)
    {
        $dto = RoleDTO::fromRequest($request->validated());
        $role = $this->roleService->store($dto);

        return $this->successResponse(RoleResource::make($role))->created('role');
    }

    public function update(UpdateRoleRequest $request, int $id)
    {
        $dto = RoleDTO::fromRequest($request->validated());
        $role = $this->roleService->update($id, $dto);

        return $this->successResponse(RoleResource::make($role))->updated('role');
    }

    public function destroy(int $id)
    {
        $this->roleService->destroy($id);

        return $this->successResponse()->deleted('role');
    }

    public function assignPermissions(AssignPermissionsRequest $request, int $id)
    {
        $role = $this->roleService->assignPermissions($id, $request->input('permissions', []));

        return $this->successResponse(RoleResource::make($role))->updated('role');
    }

    public function permissions()
    {
        $permissions = $this->roleService->getAllPermissions();

        return $this->successResponse(
            PermissionResource::collection($permissions)->collection
        );
    }
}
