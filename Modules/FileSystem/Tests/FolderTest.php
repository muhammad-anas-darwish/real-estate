<?php

namespace Modules\FileSystem\Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('super-admin');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('super-admin');

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_folder_at_root(): void
    {
        $response = $this->postJson('/api/folders', [
            'name' => 'My Folder',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_folders', [
            'name' => 'My Folder',
            'parent_id' => null,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_create_subfolder(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson('/api/folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_folders', [
            'name' => 'Child Folder',
            'parent_id' => $parent->id,
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_folder_name_in_same_parent(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();

        $this->postJson('/api/folders', [
            'name' => 'Unique',
            'parent_id' => $parent->id,
        ]);

        $response = $this->postJson('/api/folders', [
            'name' => 'Unique',
            'parent_id' => $parent->id,
        ]);
        $response->assertServerError();
    }

    /** @test */
    public function it_allows_same_folder_name_in_different_parents(): void
    {
        $parent1 = UserFolder::factory()->for($this->user)->create();
        $parent2 = UserFolder::factory()->for($this->user)->create();

        $this->postJson('/api/folders', ['name' => 'Same', 'parent_id' => $parent1->id])->assertCreated();
        $this->postJson('/api/folders', ['name' => 'Same', 'parent_id' => $parent2->id])->assertCreated();
    }

    /** @test */
    public function it_shows_folder_contents_with_breadcrumbs(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create(['name' => 'Parent']);
        $child = UserFolder::factory()->for($this->user)->withParent($folder)->create(['name' => 'Child']);

        $response = $this->getJson("/api/folders/{$child->id}");
        $response->assertOk();
        $response->assertJsonPath('data.breadcrumbs.0.name', 'Parent');
        $response->assertJsonPath('data.breadcrumbs.1.name', 'Child');
    }

    /** @test */
    public function it_can_move_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();
        $target = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_folders', [
            'id' => $folder->id,
            'parent_id' => $target->id,
        ]);
    }

    /** @test */
    public function it_cannot_move_folder_to_itself(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();

        $response = $this->postJson("/api/folders/{$folder->id}/move", [
            'target_folder_id' => $folder->id,
        ]);
        $response->assertServerError();
    }

    /** @test */
    public function it_cannot_move_folder_to_its_descendant(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();
        $child = UserFolder::factory()->for($this->user)->withParent($parent)->create();

        $response = $this->postJson("/api/folders/{$parent->id}/move", [
            'target_folder_id' => $child->id,
        ]);
        $response->assertServerError();
    }

    /** @test */
    public function it_can_rename_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create(['name' => 'Old']);

        $response = $this->postJson("/api/folders/{$folder->id}/rename", [
            'name' => 'New Name',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_folders', [
            'id' => $folder->id,
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function it_cannot_rename_to_existing_name(): void
    {
        $parent = UserFolder::factory()->for($this->user)->create();
        UserFolder::factory()->for($this->user)->withParent($parent)->create(['name' => 'Existing']);
        $folder = UserFolder::factory()->for($this->user)->withParent($parent)->create(['name' => 'Other']);

        $response = $this->postJson("/api/folders/{$folder->id}/rename", [
            'name' => 'Existing',
        ]);
        $response->assertServerError();
    }

    /** @test */
    public function it_cannot_delete_non_empty_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();
        UserFolder::factory()->for($this->user)->withParent($folder)->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertServerError();
        $this->assertDatabaseHas('user_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_can_delete_empty_folder(): void
    {
        $folder = UserFolder::factory()->for($this->user)->create();

        $response = $this->deleteJson("/api/folders/{$folder->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('user_folders', ['id' => $folder->id]);
    }

    /** @test */
    public function it_cannot_access_other_users_folder(): void
    {
        $folder = UserFolder::factory()->for($this->otherUser)->create();

        $response = $this->getJson("/api/folders/{$folder->id}");
        $response->assertForbidden();
    }

    /** @test */
    public function it_cannot_create_subfolder_in_other_users_folder(): void
    {
        $folder = UserFolder::factory()->for($this->otherUser)->create();

        $response = $this->postJson('/api/folders', [
            'name' => 'Intruder',
            'parent_id' => $folder->id,
        ]);
        $response->assertUnprocessable();
    }
}
