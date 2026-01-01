<?php

namespace App\Traits;

trait ApplyPermissions
{
    private $crudMethods = [
        'show' => 'show',
        'index' => 'list',
        'store' => 'create',
        'update' => 'edit',
        'destroy' => 'delete',
    ];

    /**
     * Apply permissions to controller methods with guard support
     *
     * @param string $name The permission name prefix
     * @param array $guards Array of guards to apply (e.g., ['web', 'api'])
     * @param array $crudMethods Array of CRUD methods to apply
     * @param array $additionalMethods Additional methods with permissions
     * @param array $multiplePermissions Methods requiring multiple permissions
     */
    public function applyPermissions(
        string $name,
        array $crudMethods = [],
        array $additionalMethods = [],
        array $multiplePermissions = []
    ) {
        $mergedMethods = $this->filterCrudMethods($crudMethods);
        $allMethods = array_merge($mergedMethods, $additionalMethods);

        // Apply single permissions
        foreach ($allMethods as $method => $permission) {
            if (!isset($multiplePermissions[$method])) {
                $this->applySinglePermission($name, $method, $permission);
            }
        }

        // Apply multiple permissions
        foreach ($multiplePermissions as $method => $permissions) {
            $this->applyMultiplePermissions($name, $method, $permissions);
        }
    }

    /**
     * Apply a single permission to a method with guard support
     */
    private function applySinglePermission(
        string $name,
        string $method,
        string $permission,
    ) {
        $middleware = $this->buildPermissionMiddleware($name.'.'.$permission);
        $this->middleware($middleware, ['only' => [$method]]);
    }

    /**
     * Apply multiple permissions to a method with guard support
     */
    private function applyMultiplePermissions(
        string $name,
        string $method,
        array $permissions,
    ) {
        $middleware = [];
        foreach ($permissions as $permission) {
            $middleware[] = $this->buildPermissionMiddleware($name.'.'.$permission);
        }
        $this->middleware($middleware, ['only' => [$method]]);
    }

    /**
     * Build permission middleware string with guard support
     */
    private function buildPermissionMiddleware(string $permission): string
    {
        // Create guard-specific middleware
        $middleware = "permission:$permission";

        return $middleware;
    }

    /**
     * Filter CRUD methods based on input keys
     */
    private function filterCrudMethods(array $keys): array
    {
        return array_intersect_key($this->crudMethods, array_flip($keys));
    }
}
