<?php

namespace Modules\FileSystem\Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\Auth\Entities\User;
use Modules\Core\Database\Seeders\CountriesAndCitiesSeeder;
use Modules\FileSystem\Entities\UserFolder;
use Modules\RealEstate\Entities\Property;
use Tests\TestCase;

class PropertyFolderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CountriesAndCitiesSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('super-admin');

        $this->property = Property::factory()
            ->publishedBy($this->user)
            ->approved()
            ->create();

        UserFolder::factory()
            ->for($this->user)
            ->property()
            ->state([
                'source_type' => 'Property',
                'source_id' => $this->property->id,
            ])
            ->create();

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_prevents_deleting_property_folder(): void
    {
        $folder = UserFolder::factory()
            ->for($this->user)
            ->property()
            ->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertForbidden();
        $this->assertDatabaseHas('user_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_prevents_moving_property_folder(): void
    {
        $folder = UserFolder::factory()
            ->for($this->user)
            ->property()
            ->create();

        $target = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertForbidden();
    }

    /** @test */
    public function it_cannot_access_other_users_property_folder(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('super-admin');

        $folder = UserFolder::factory()
            ->for($otherUser)
            ->property()
            ->create();

        $response = $this->getJson("/api/folders/{$folder->id}");
        $response->assertForbidden();
    }

    /** @test */
    public function it_can_access_property_files_endpoint(): void
    {
        $response = $this->getJson("/api/properties/{$this->property->id}/files");
        $response->assertOk();
        $response->assertJsonPath('data.files', []);
    }
}
