<?php

namespace Modules\Core\TemporaryFile\Library;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        // Use the UUID instead of the ID
        return $media->uuid . '/';
    }

    /*
     * Get the path for conversions of the given media.
     */
    public function getPathForConversions(Media $media): string
    {
        return $media->uuid . '/conversions/';
    }

    /*
     * Get the path for responsive images of the given media.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $media->uuid . '/responsive-images/';
    }
}
