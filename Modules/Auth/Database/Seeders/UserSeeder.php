<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Entities\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::where('email', 'admin@admin.com')->exists()) {
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
            ]);
        }

        User::factory(10)->create();

        $admin = User::where('email', 'admin@admin.com')->first();
        $superAdmin = Role::where('name', 'super-admin')->first();

        if ($admin && $superAdmin) {
            $admin->assignRole($superAdmin);
        }
    }
}
