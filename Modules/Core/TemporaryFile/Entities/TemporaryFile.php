<?php

namespace Modules\Core\TemporaryFile\Entities;

use App\Models\BaseModel;

class TemporaryFile extends BaseModel
{
    public $table = 'temporary_files';

    public static $rules = [ 
        // 'tasks' => [
        //     'multiple_files' => true,
        //     'extensions' => null, // you can pass array of extensions
        //     'max_size' => 2048,
        // ]
    ];

    public static function getRules(string $type): array
    {
        return self::$rules[$type] ?? [];
    }

    public static function supportsMultipleFiles(string $type): bool
    {
        $rules = self::getRules($type);
        return isset($rules['multiple_files']) && $rules['multiple_files'] === true;
    }
}
