<?php

namespace Modules\FileSystem\Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Enums\FileType;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private UserFolder $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('super-admin');

        $this->folder = UserFolder::factory()->for($this->user)->create();

        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_create_text_file(): void
    {
        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'notes.txt',
            'content' => 'Hello world',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'notes.txt',
            'content' => 'Hello world',
            'file_type' => FileType::TEXT->value,
        ]);
    }

    /** @test */
    public function it_can_create_empty_text_file(): void
    {
        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'empty.txt',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'empty.txt',
            'content' => '',
            'size' => 0,
        ]);
    }

    /** @test */
    public function it_can_update_text_file_content(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['content' => 'Old', 'size' => 3]);

        $response = $this->putJson("/api/files/{$file->id}/text", [
            'content' => 'New content here',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', [
            'id' => $file->id,
            'content' => 'New content here',
        ]);
    }

    /** @test */
    public function it_can_rename_file(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['name' => 'old.txt']);

        $response = $this->postJson("/api/files/{$file->id}/rename", [
            'name' => 'new.txt',
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', ['id' => $file->id, 'name' => 'new.txt']);
    }

    /** @test */
    public function it_can_upload_image(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $image,
            'name' => 'photo.jpg',
        ]);
        $response->assertCreated();
        $this->assertDatabaseHas('user_files', [
            'name' => 'photo.jpg',
            'file_type' => FileType::IMAGE->value,
        ]);
    }

    /** @test */
    public function it_rejects_invalid_image_extension(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $file,
            'name' => 'document.pdf',
        ]);
        $response->assertUnprocessable();
    }

    /** @test */
    public function it_rejects_image_exceeding_max_size(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('large.jpg')->size(11000);

        $response = $this->postJson('/api/files/image', [
            'folder_id' => $this->folder->id,
            'image' => $image,
            'name' => 'large.jpg',
        ]);
        $response->assertUnprocessable();
    }

    /** @test */
    public function it_can_delete_file(): void
    {
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['size' => 100]);

        $response = $this->deleteJson("/api/files/{$file->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('user_files', ['id' => $file->id]);
    }

    /** @test */
    public function it_can_move_file_to_another_folder(): void
    {
        $target = UserFolder::factory()->for($this->user)->create();
        $file = UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create();

        $response = $this->postJson("/api/files/{$file->id}/move", [
            'target_folder_id' => $target->id,
        ]);
        $response->assertOk();
        $this->assertDatabaseHas('user_files', ['id' => $file->id, 'folder_id' => $target->id]);
    }

    /** @test */
    public function it_cannot_access_other_users_file(): void
    {
        $otherFolder = UserFolder::factory()->for(User::factory())->create();
        $file = UserFile::factory()
            ->for(User::factory(), 'user')
            ->for($otherFolder, 'folder')
            ->create();

        $response = $this->getJson("/api/files/{$file->id}");
        $response->assertForbidden();
    }

    /** @test */
    public function it_rejects_duplicate_file_name_in_same_folder(): void
    {
        UserFile::factory()
            ->for($this->user)
            ->for($this->folder, 'folder')
            ->create(['name' => 'notes.txt']);

        $response = $this->postJson('/api/files/text', [
            'folder_id' => $this->folder->id,
            'name' => 'notes.txt',
            'content' => 'Duplicate',
        ]);
        $response->assertServerError();
    }
}
