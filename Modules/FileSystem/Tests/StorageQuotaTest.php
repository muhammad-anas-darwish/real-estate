<?php

namespace Modules\FileSystem\Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\StorageLimit;
use Tests\TestCase;

class StorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('super-admin');

        StorageLimit::where('user_id', $this->user->id)->update([
            'used_bytes' => 80_000_000,
        ]);

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_returns_storage_status(): void
    {
        $response = $this->getJson('/api/storage/status');
        $response->assertOk();
        $response->assertJsonPath('data.quota_bytes', 104_857_600);
        $response->assertJsonPath('data.used_bytes', 80_000_000);
    }

    /** @test */
    public function it_detects_near_limit(): void
    {
        StorageLimit::where('user_id', $this->user->id)
            ->update(['used_bytes' => 90_000_000]);

        $response = $this->getJson('/api/storage/status');
        $response->assertJsonPath('data.is_near_limit', true);
    }

    /** @test */
    public function it_lists_available_packages(): void
    {
        $response = $this->getJson('/api/storage/packages');
        $response->assertOk();
        $packages = $response->json('data.packages');
        $this->assertNotEmpty($packages);
        foreach ($packages as $pkg) {
            $this->assertGreaterThan(104_857_600, $pkg['quota_bytes']);
        }
    }

    /** @test */
    public function it_can_upgrade_storage(): void
    {
        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'medium',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('storage_limits', [
            'user_id' => $this->user->id,
            'package_type' => 'medium',
        ]);
    }

    /** @test */
    public function it_rejects_upgrade_to_lower_or_equal_package(): void
    {
        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'free',
        ]);
        $response->assertServerError();
    }

    /** @test */
    public function it_cannot_upgrade_beyond_max(): void
    {
        StorageLimit::where('user_id', $this->user->id)->update([
            'package_type' => 'max',
            'quota_bytes' => 5_368_709_120,
        ]);

        $response = $this->postJson('/api/storage/upgrade', [
            'package_type' => 'max',
        ]);
        $response->assertServerError();
    }
}
