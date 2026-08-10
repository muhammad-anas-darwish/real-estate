<?php

namespace Modules\RealEstate\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Modules\Auth\Entities\User;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\DTOs\CreateRentalCardDTO;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\RentalCard;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Services\RentalCardService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class RentalCardMediaTest extends TestCase
{
    use RefreshDatabase;

    private RentalCardService $service;

    private User $owner;

    private User $tenant;

    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RentalCardService::class);

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);

        $this->owner = User::factory()->create();
        $this->tenant = User::factory()->create();

        $this->property = Property::factory()->create([
            'publisher_id' => $this->owner->id,
            'status' => PropertyStatus::APPROVED,
        ]);
    }

    private function makeCreateDto(array $photos = []): CreateRentalCardDTO
    {
        return CreateRentalCardDTO::fromRequest([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'tenant_user_id' => $this->tenant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'pre_rental_photos' => $photos,
        ]);
    }

    public function test_attach_pre_rental_photos_to_card(): void
    {
        $photos = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
            UploadedFile::fake()->image('photo3.webp'),
        ];

        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto($photos));

        $this->assertCount(3, $card->fresh()->getMedia('pre_rental'));
        $this->assertEquals(3, Media::where('model_type', RentalCard::class)
            ->where('model_id', $card->id)
            ->where('collection_name', 'pre_rental')
            ->count());
    }

    public function test_no_photos_means_no_media(): void
    {
        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto([]));

        $this->assertCount(0, $card->fresh()->getMedia('pre_rental'));
    }

    public function test_soft_deleting_card_preserves_media(): void
    {
        $photos = [UploadedFile::fake()->image('photo.jpg')];

        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto($photos));

        $this->assertCount(1, $card->fresh()->getMedia('pre_rental'));

        $card->delete();

        $this->assertCount(1, $card->fresh()->getMedia('pre_rental'));
    }

    public function test_force_deleting_card_removes_media(): void
    {
        $photos = [UploadedFile::fake()->image('photo.jpg')];

        $card = $this->actingAs($this->owner)
            ->service->create($this->makeCreateDto($photos));

        $this->assertCount(1, $card->fresh()->getMedia('pre_rental'));

        $card->forceDelete();

        $this->assertCount(0, Media::where('model_type', RentalCard::class)
            ->where('collection_name', 'pre_rental')
            ->get());
    }

    public function test_dto_normalizes_single_uploaded_file(): void
    {
        $single = UploadedFile::fake()->image('solo.jpg');

        $dto = CreateRentalCardDTO::fromRequest([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'pre_rental_photos' => $single,
        ]);

        $this->assertCount(1, $dto->pre_rental_photos);
        $this->assertInstanceOf(UploadedFile::class, $dto->pre_rental_photos[0]);
    }

    public function test_dto_ignores_non_file_entries(): void
    {
        $dto = CreateRentalCardDTO::fromRequest([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'pre_rental_photos' => [123, 'string', null, UploadedFile::fake()->image('valid.jpg')],
        ]);

        $this->assertCount(1, $dto->pre_rental_photos);
    }
}
