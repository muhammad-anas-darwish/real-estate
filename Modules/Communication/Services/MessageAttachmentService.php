<?php

namespace Modules\Communication\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageAttachmentService
{
    private const MAX_IMAGE_SIZE = 5 * 1024 * 1024;
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const IMAGE_MIMETYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const FILE_MIMETYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function upload(UploadedFile $file, string $type = 'file'): string
    {
        $maxSize = $type === 'image' ? self::MAX_IMAGE_SIZE : self::MAX_FILE_SIZE;
        $allowedTypes = $type === 'image' ? self::IMAGE_MIMETYPES : self::FILE_MIMETYPES;

        $this->validate($file, $type, $maxSize, $allowedTypes);

        $folder = $type === 'image' ? 'chat/images' : 'chat/files';
        $disk = config('communication.chat.attachment_disk', 'local');

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($folder, $filename, $disk);
    }

    public function getUrl(string $path): string
    {
        $disk = config('communication.chat.attachment_disk', 'local');

        if ($disk === 's3') {
            return $this->getSignedUrl($path, $disk);
        }

        return Storage::disk($disk)->url($path);
    }

    public function delete(string $path): void
    {
        $disk = config('communication.chat.attachment_disk', 'local');
        Storage::disk($disk)->delete($path);
    }

    public function isImage(string $path): bool
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }

    protected function validate(UploadedFile $file, string $type, int $maxSize, array $allowedMimetypes): void
    {
        if ($file->getSize() > $maxSize) {
            $maxSizeMb = $maxSize / (1024 * 1024);
            throw new \InvalidArgumentException(
                "File size exceeds maximum of {$maxSizeMb}MB"
            );
        }

        $mimetype = $file->getMimeType();

        if (! in_array($mimetype, $allowedMimetypes)) {
            throw new \InvalidArgumentException(
                "File type {$mimetype} is not allowed for {$type}"
            );
        }
    }

    protected function getSignedUrl(string $path, string $disk): string
    {
        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes(60)
        );
    }
}