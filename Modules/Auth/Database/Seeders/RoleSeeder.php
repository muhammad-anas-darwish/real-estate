<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super-admin' => 'Super Admin',
            'admin' => 'Admin',
            'user' => 'User',
        ];

        foreach ($roles as $name => $label) {
            SpatieRole::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}
