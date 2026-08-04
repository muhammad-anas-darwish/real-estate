<?php

namespace Modules\Core\TemporaryFile\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadMediaHelper
{
    public static array $allowedImageExtensions = [
        '.png',
        '.jpg',
        '.jpeg',
        '.svg',
    ];

    public static array $imageMimeTypes = [
        'image/apng',
        'image/avif',
        'image/gif',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/svg+xml',
        'image/svg',
        'image/webp',
    ];

    public static array $pdfMimeTypes = [
        'application/pdf',
    ];

    public static array $videoMimeTypes = [
        'application/annodex',
        'application/mp4',
        'application/ogg',
        'application/vnd.rn-realmedia',
        'application/x-matroska',
        'video/3gpp',
        'video/3gpp2',
        'video/annodex',
        'video/divx',
        'video/flv',
        'video/h264',
        'video/mp4',
        'video/mp4v-es',
        'video/mpeg',
        'video/mpeg-2',
        'video/mpeg4',
        'video/ogg',
        'video/ogm',
        'video/quicktime',
        'video/ty',
        'video/vdo',
        'video/vivo',
        'video/vnd.rn-realvideo',
        'video/vnd.vivo',
        'video/webm',
        'video/x-bin',
        'video/x-cdg',
        'video/x-divx',
        'video/x-dv',
        'video/x-flv',
        'video/x-la-asf',
        'video/x-m4v',
        'video/x-matroska',
        'video/x-motion-jpeg',
        'video/x-ms-asf',
        'video/x-ms-dvr',
        'video/x-ms-wm',
        'video/x-ms-wmv',
        'video/x-msvideo',
        'video/x-sgi-movie',
        'video/x-tivo',
        'video/avi',
        'video/x-ms-asx',
        'video/x-ms-wvx',
        'video/x-ms-wmx',
    ];

    public static function imageRules(): array
    {
        if (request()->isMethod('PUT')) {
            return [
                'nullable',
                'mimetypes:'.implode(',', self::$imageMimeTypes),
            ];
        }

        return [
            'required',
            'mimetypes:'.implode(',', self::$imageMimeTypes),
        ];
    }

    public static function getImageUrl($model, $collection): ?string
    {
        return $model->getFirstMediaUrl($collection);
    }

    public static function getMultiImageUrls($model, $collection): array
    {
        $images = [];
        foreach ($model->getMedia($collection) as $media) {
            $images[] = $media->getUrl();
        }

        return $images;
    }

    public static function upload(?UploadedFile $media, $model, $collectionName): void
    {
        if ($media) {
            $fileName = Str::uuid().'-'.Str::slug($collectionName).'.'.$media->extension();
            $model->addMedia($media)->usingFileName($fileName)->toMediaCollection($collectionName);
        }
    }
}
