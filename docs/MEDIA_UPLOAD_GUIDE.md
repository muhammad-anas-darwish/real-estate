# Media Upload Guide

## Overview

Uses **Spatie Media Library** for image/file handling. Implements `InteractsWithMedia` trait on entities.

---

## Entity Setup

### 1. Implement HasMedia Interface

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Property extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;
}
```

### 2. Register Media Collections

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('main_image')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

    $this->addMediaCollection('gallery')
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

    $this->addMediaCollection('attachments')
        ->acceptsMimeTypes(['application/pdf', 'application/msword']);
}
```

### 3. Register Media Conversions (thumbnails)

```php
public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->width(300)
        ->height(200)
        ->sharpen(10);

    $this->addMediaConversion('medium')
        ->width(800)
        ->height(600)
        ->sharpen(10);
}
```

---

## Uploading Files

### Using UploadMediaHelper (Recommended)

```php
use Modules\Core\TemporaryFile\Helpers\UploadMediaHelper;

UploadMediaHelper::upload($file, $model, 'main_image');
UploadMediaHelper::upload($file, $model, 'gallery');
```

### Direct Upload

```php
use Illuminate\Support\Str;

$fileName = Str::uuid() . "-" . Str::slug('collection-name') . "." . $media->extension();
$model->addMedia($media)
    ->usingFileName($fileName)
    ->toMediaCollection('collection_name');
```

---

## Temporary File Flow

For staged uploads before final save:

### 1. TemporaryFileService

Stores files in `storage/app/files/tmp/{folder}` and creates `TemporaryFile` entity.

### 2. MediaSyncService

Moves temp files to media collection:

```php
public function syncFiles(Model $model, array $files, string $ruleName, string $collectionName = 'attachments'): void
```

### 3. StoreTemporaryFileRequest

```php
public function rules(): array
{
    return [
        'file' => ['required', 'file', 'max:10240', 'mimes:jpeg,png,webp,pdf,doc,docx'],
    ];
}
```

---

## Acceptable MIME Types

| Type | MIME Types |
|------|------------|
| Images | `image/jpeg`, `image/png`, `image/webp` |
| Documents | `application/pdf`, `application/msword`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document` |

---

## Accessing Media URLs

### On Model (Accessor Methods)

```php
public function getMainImageUrlAttribute(): ?string
{
    return $this->getFirstMediaUrl('main_image');
}

public function getMainImageThumbUrlAttribute(): ?string
{
    return $this->getFirstMediaUrl('main_image', 'thumb');
}

public function getGalleryUrlsAttribute(): array
{
    return $this->getMedia('gallery')->map(fn($media) => [
        'url' => $media->getUrl(),
        'thumb' => $media->getUrl('thumb'),
        'medium' => $media->getUrl('medium'),
    ])->toArray();
}
```

### In Resource

```php
protected function getCustomData(): array
{
    return [
        'main_image' => $this->main_image_url,
        'main_image_thumb' => $this->main_image_thumb_url,
        'gallery' => $this->gallery_urls,
    ];
}
```

---

## Deleting Media

```php
$model->clearMediaCollection('gallery');
$model->addMedia($newFile)->toMediaCollection('main_image');
```

---

## Form Request Validation

```php
public function rules(): array
{
    return [
        'main_image' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
        'gallery' => ['nullable', 'array'],
        'gallery.*' => ['nullable', 'file', 'mimes:jpeg,png,webp', 'max:5120'],
        'existing_gallery' => ['nullable', 'array'],
        'existing_gallery.*.id' => ['nullable', 'integer'],
    ];
}
```

---

## Checklist for Adding Media to Model

1. Add `InteractsWithMedia` trait to entity
2. Implement `registerMediaCollections()` method
3. Add `registerMediaConversions()` for thumbnails
4. Create accessor methods for media URLs
5. Update resource `getCustomData()` to include media
6. Add validation rules in FormRequest