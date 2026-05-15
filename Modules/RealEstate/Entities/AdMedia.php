<?php

namespace Modules\RealEstate\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\RealEstate\Enums\AdMediaType;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AdMedia extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'ad_media';

    protected $fillable = [
        'ad_id',
        'file_path',
        'media_type',
        'sort_order',
    ];

    protected $casts = [
        'media_type' => AdMediaType::class,
        'sort_order' => 'integer',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

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
}
