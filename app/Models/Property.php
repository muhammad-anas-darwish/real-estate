<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'country',
        'city',
        'longitude',
        'latitude',
        'rooms',
        'bathrooms',
        'total_area',
        'publisher_id',
        'approved_by',
        'detailed_info',
        'price',
        'photos',
        'main_image',
    ];

    protected $casts = [
        'photos' => 'array',
        'longitude' => 'decimal:8',
        'latitude' => 'decimal:8',
        'total_area' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function publisher()
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}