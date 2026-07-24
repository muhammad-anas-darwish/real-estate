# المهمة #20: تأسيس بيانات نظام الملفات الشخصي (Per-User File System — Data Foundation)

> **التقرير المصدر:** `docs/ideas/per-user-file-system/report.md` (القواعد 1–14، 18–27، معايير القبول 1–22)
> **الهدف:** إنشاء الطبقة الأساسية (Migration + Models + Enums + DTOs + Permissions + ServiceProvider) لميزة نظام الملفات الشخصي — دون أي منطق خدمة أو API حتى الآن.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🔴 عالية
> **الجهد المقدّر:** 0.5 – 1 يوم
> **الاعتمادية:** لا شيء (هذه الخطة هي الأساس الذي تبني عليه الخطط #21 → #24)
> **راجع:** `report.md` السطور 110–147 (القواعد) و196–222 (معايير القبول)

---

## الوضع الحالي

لا يوجد في النظام اليوم أي مفهوم لـ "نظام ملفات شخصي" أو جداول تخزّن مجلدات وملفات المستخدمين. كل مستخدم يملك عقارات (`properties` مرتبطة بـ `publisher_id`) لكن لا يوجد مجلد ملفات تلقائي للعقار. الملفات المرفوعة حاليًا تُخزَّن فقط عبر `Spatie\MediaLibrary` على مستوى الـ model الواحد (مثل صور العقار)، دون تداخل هرمي أو مفهوم مجلدات متداخلة.

هذه الخطة تنشئ:
- وحدة `Modules/FileSystem/` كاملة الهيكل (ServiceProvider + مجلدات Routes, Database, Http, Services, Entities, Enums, DTOs, Policies).
- 3 جداول: `user_folders`, `user_files`, `storage_limits`.
- 3 نماذج + 2 Enums + 6 DTOs + مجموعة صلاحيات `files.*`.

---

## المرحلة 1: إنشاء هيكل الوحدة + ServiceProvider (~ 30 دقيقة)

**الهدف:** تجهيز `Modules/FileSystem` كوحدة مستقلة قابلة للتسجيل التلقائي، بنفس نمط `Modules\Crm`.

### 1.1 هيكل المجلدات

```
Modules/FileSystem/
├── Providers/
│   └── FileSystemServiceProvider.php
├── Database/
│   ├── Migrations/
│   └── Factories/
├── Routes/
│   └── api.php
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Services/
├── Entities/
├── Enums/
├── DTOs/
└── Policies/
```

### 1.2 ServiceProvider

`Modules/FileSystem/Providers/FileSystemServiceProvider.php`:

```php
<?php

namespace Modules\FileSystem\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\FileSystem\Policies\FolderPolicy;
use Modules\FileSystem\Policies\FilePolicy;

class FileSystemServiceProvider extends ServiceProvider
{
    protected $policies = [
        \Modules\FileSystem\Entities\UserFolder::class => FolderPolicy::class,
        \Modules\FileSystem\Entities\UserFile::class => FilePolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerPolicies();
    }
}
```

> **ملاحظة:** الـ policies تُسجَّل هنا في هذه الخطة فارغة (ترجع `true` مؤقتًا)، وتُملأ بالكامل في الخطة #21.

### 1.3 تسجيل PSR-4

لا حاجة لإضافة `composer.json` يدويًا — Laravel auto-discovery يلتقط `FileSystemServiceProvider` تلقائيًا طالما namespace `Modules\FileSystem\` موجود في composer.json.

تأكّد من وجود السطر التالي في `composer.json`:

```json
"Modules\\FileSystem\\": "Modules/FileSystem/"
```

ثم `composer dump-autoload`.

---

## المرحلة 2: Migration لجداول نظام الملفات (~ 2-3 ساعات)

**الهدف:** إنشاء 3 جداول أساسية.

### 2.1 Migration لجدول `user_folders`

`Modules/FileSystem/Database/Migrations/2026_07_03_000001_create_user_folders_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('user_folders')->nullOnDelete();
            $table->string('name');
            $table->string('folder_type')->default('regular'); // regular | property | general
            $table->foreignId('source_id')->nullable()->comment('معرّف الكيان المرتبط (property_id مثلاً)');
            $table->string('source_type')->nullable()->comment('نوع الكيان المرتبط (Property مثلاً)');
            $table->boolean('is_protected')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'parent_id']);
            $table->index(['source_type', 'source_id']);
            $table->unique(['parent_id', 'name', 'user_id'], 'unique_folder_name_per_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_folders');
    }
};
```

**توضيح الأعمدة:**
- `folder_type`: يحدد نوع المجلد — `regular` (عادي ينشئه المستخدم)، `property` (مرتبط بعقار، محمي)، `general` (المجلد العام).
- `source_id` + `source_type`: polymorphic — يربط المجلد بكيان آخر (مثل `Property`). يُستخدَم لتحديد مجلدات العقارات.
- `is_protected`: يمنع نقل/حذف/إعادة تسمية المجلد. يُضبَط `true` لمجلدات العقارات والمجلد العام.
- `parent_id`: يدعم التداخل الهرمي (null للجذر).
- `unique_folder_name_per_parent`: يمنع تكرار الاسم داخل نفس المجلد الأب ولنفس المستخدم.

### 2.2 Migration لجدول `user_files`

`Modules/FileSystem/Database/Migrations/2026_07_03_000002_create_user_files_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('user_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_type')->default('text'); // text | image
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0); // بالبايت
            $table->text('content')->nullable(); // محتوى الملفات النصية فقط
            $table->string('file_path')->nullable(); // مسار الصور على القرص
            $table->timestamps();

            $table->index(['user_id', 'folder_id']);
            $table->index(['user_id', 'file_type']);
            $table->unique(['folder_id', 'name', 'user_id'], 'unique_file_name_per_folder');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_files');
    }
};
```

**توضيح الأعمدة:**
- `content`: يُملأ فقط للملفات النصية (يحوي النص كما كتبه المستخدم). فارغ للصور.
- `file_path`: يُملأ فقط للصور (مسار التخزين). فارغ للنصوص.
- `size`: الحجم بالبايت يُجمَع لحساب حصة المستخدم التخزينية.
- `mime_type`: يُستخدم للتحقق من نوع الملف وعرضه بشكل صحيح.
- `unique_file_name_per_folder`: يمنع تكرار الاسم داخل نفس المجلد.

### 2.3 Migration لجدول `storage_limits`

`Modules/FileSystem/Database/Migrations/2026_07_03_000003_create_storage_limits_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('quota_bytes')->default(104_857_600); // 100 MB
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->string('package_type')->default('free'); // free | small | medium | large | max
            $table->timestamp('package_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_limits');
    }
};
```

**ملاحظة:** `104_857_600` = 100 × 1024 × 1024 = 100 MB.

---

## المرحلة 3: Enums (~ 30 دقيقة)

### 3.1 `FileType` Enum

`Modules/FileSystem/Enums/FileType.php`:

```php
<?php

namespace Modules\FileSystem\Enums;

enum FileType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';

    public function allowedExtensions(): array
    {
        return match ($this) {
            self::TEXT => [],
            self::IMAGE => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        };
    }

    public function maxSizeBytes(): int
    {
        return match ($this) {
            self::TEXT => 5_242_880,   // 5 MB
            self::IMAGE => 10_485_760,  // 10 MB
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### 3.2 `StoragePackageType` Enum

`Modules/FileSystem/Enums/StoragePackageType.php`:

```php
<?php

namespace Modules\FileSystem\Enums;

enum StoragePackageType: string
{
    case FREE = 'free';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case MAX = 'max';

    public function quotaBytes(): int
    {
        return match ($this) {
            self::FREE   => 104_857_600,    // 100 MB
            self::SMALL  => 524_288_000,    // 500 MB
            self::MEDIUM  => 1_073_741_824,  // 1 GB
            self::LARGE => 3_221_225_472,   // 3 GB
            self::MAX    => 5_368_709_120,   // 5 GB
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::SMALL => 'Small',
            self::MEDIUM => 'Medium',
            self::LARGE => 'Large',
            self::MAX => 'Max',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

---

## المرحلة 4: Models + Factories (~ 2-3 ساعات)

### 4.1 `UserFolder` Model

`Modules/FileSystem/Entities/UserFolder.php`:

```php
<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Entities\User;

class UserFolder extends BaseModel
{
    use HasFactory;

    protected $table = 'user_folders';

    protected $fillable = [
        'user_id', 'parent_id', 'name', 'folder_type',
        'source_id', 'source_type', 'is_protected',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'source_id' => 'integer',
    ];

    protected static $filterableColumns = [
        'user_id', 'parent_id', 'folder_type', 'is_protected',
    ];

    protected static $multiFilterableColumns = ['id', 'user_id'];

    protected static $searchableColumns = ['name'];

    protected static $dateFilterableColumns = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(UserFile::class, 'folder_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeProtected($query)
    {
        return $query->where('is_protected', true);
    }

    public function isPropertyFolder(): bool
    {
        return $this->folder_type === 'property';
    }

    public function isGeneralFolder(): bool
    {
        return $this->folder_type === 'general';
    }

    public function isMovable(): bool
    {
        return ! $this->is_protected;
    }

    /**
     * تحقق إن كان المجلد من نسل مجلد آخر (لمنع الحلقات عند النقل)
     */
    public function isDescendantOf(self $potentialParent): bool
    {
        $current = $this->parent;

        while ($current) {
            if ($current->id === $potentialParent->id) {
                return true;
            }
            $current = $current->parent;
        }

        return false;
    }

    protected static function newFactory()
    {
        return \Modules\FileSystem\Database\Factories\UserFolderFactory::new();
    }
}
```

### 4.2 `UserFile` Model

`Modules/FileSystem/Entities/UserFile.php`:

```php
<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Enums\FileType;

class UserFile extends BaseModel
{
    use HasFactory;

    protected $table = 'user_files';

    protected $fillable = [
        'user_id', 'folder_id', 'name', 'file_type',
        'mime_type', 'size', 'content', 'file_path',
    ];

    protected $casts = [
        'file_type' => FileType::class,
        'size' => 'integer',
    ];

    protected static $filterableColumns = [
        'user_id', 'folder_id', 'file_type',
    ];

    protected static $multiFilterableColumns = ['id', 'folder_id'];

    protected static $searchableColumns = ['name'];

    protected static $dateFilterableColumns = ['created_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(UserFolder::class, 'folder_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInFolder($query, int $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    public function scopeOfType($query, FileType $type)
    {
        return $query->where('file_type', $type);
    }

    public function isText(): bool
    {
        return $this->file_type === FileType::TEXT;
    }

    public function isImage(): bool
    {
        return $this->file_type === FileType::IMAGE;
    }

    protected static function newFactory()
    {
        return \Modules\FileSystem\Database\Factories\UserFileFactory::new();
    }
}
```

### 4.3 `StorageLimit` Model

`Modules/FileSystem/Entities/StorageLimit.php`:

```php
<?php

namespace Modules\FileSystem\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Enums\StoragePackageType;

class StorageLimit extends BaseModel
{
    protected $table = 'storage_limits';

    protected $fillable = [
        'user_id', 'quota_bytes', 'used_bytes',
        'package_type', 'package_expires_at',
    ];

    protected $casts = [
        'quota_bytes' => 'integer',
        'used_bytes' => 'integer',
        'package_type' => StoragePackageType::class,
        'package_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUsedPercentageAttribute(): float
    {
        if ($this->quota_bytes <= 0) {
            return 100.0;
        }
        return round(($this->used_bytes / $this->quota_bytes) * 100, 2);
    }

    public function getRemainingBytesAttribute(): int
    {
        return max(0, $this->quota_bytes - $this->used_bytes);
    }

    public function isExceeded(): bool
    {
        return $this->used_bytes >= $this->quota_bytes;
    }

    public function isNearLimit(): bool
    {
        return $this->usedPercentage >= 80.0;
    }

    public function hasAvailableSpace(int $bytes): bool
    {
        return ($this->used_bytes + $bytes) <= $this->quota_bytes;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
```

### 4.4 Factories

`Modules/FileSystem/Database/Factories/UserFolderFactory.php`:

```php
<?php

namespace Modules\FileSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFolder;

class UserFolderFactory extends Factory
{
    protected $model = UserFolder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->word(),
            'folder_type' => 'regular',
            'is_protected' => false,
        ];
    }

    public function property(): static
    {
        return $this->state(fn () => [
            'folder_type' => 'property',
            'is_protected' => true,
            'name' => 'Property Folder',
        ]);
    }

    public function general(): static
    {
        return $this->state(fn () => [
            'folder_type' => 'general',
            'is_protected' => true,
            'name' => 'General',
        ]);
    }

    public function withParent(UserFolder $parent): static
    {
        return $this->state(fn () => [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
        ]);
    }
}
```

`Modules/FileSystem/Database/Factories/UserFileFactory.php`:

```php
<?php

namespace Modules\FileSystem\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Auth\Entities\User;
use Modules\FileSystem\Entities\UserFile;
use Modules\FileSystem\Entities\UserFolder;
use Modules\FileSystem\Enums\FileType;

class UserFileFactory extends Factory
{
    protected $model = UserFile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'folder_id' => UserFolder::factory(),
            'name' => fake()->word().'.txt',
            'file_type' => FileType::TEXT,
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(1, 10000),
            'content' => fake()->paragraph(),
            'file_path' => null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn () => [
            'file_type' => FileType::IMAGE,
            'mime_type' => 'image/jpeg',
            'name' => fake()->word().'.jpg',
            'content' => null,
            'file_path' => 'user-files/'.fake()->uuid().'.jpg',
            'size' => fake()->numberBetween(10_000, 500_000),
        ]);
    }

    public function emptyFile(): static
    {
        return $this->state(fn () => [
            'content' => '',
            'size' => 0,
        ]);
    }
}
```

---

## المرحلة 5: DTOs (~ 1.5-2 ساعات)

### 5.1 `CreateFolderDTO`

`Modules/FileSystem/DTOs/CreateFolderDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class CreateFolderDTO implements DTOInterface
{
    public function __construct(
        public int $user_id,
        public ?int $parent_id,
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: (int) $data['user_id'],
            parent_id: isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
        ];
    }
}
```

### 5.2 `UpdateFolderDTO`

`Modules/FileSystem/DTOs/UpdateFolderDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class UpdateFolderDTO implements DTOInterface
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(name: $data['name']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
```

### 5.3 `CreateTextFileDTO`

`Modules\FileSystem/DTOs\CreateTextFileDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class CreateTextFileDTO implements DTOInterface
{
    public function __construct(
        public int $user_id,
        public int $folder_id,
        public string $name,
        public string $content = '',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            user_id: (int) $data['user_id'],
            folder_id: (int) $data['folder_id'],
            name: $data['name'],
            content: $data['content'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'folder_id' => $this->folder_id,
            'name' => $this->name,
            'content' => $this->content,
        ];
    }
}
```

### 5.4 `UpdateTextFileDTO`

`Modules/FileSystem/DTOs/UpdateTextFileDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class UpdateTextFileDTO implements DTOInterface
{
    public function __construct(
        public ?string $name,
        public ?string $content,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            content: $data['content'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'content' => $this->content,
        ], fn ($v) => $v !== null);
    }
}
```

### 5.5 `MoveItemDTO`

`Modules/FileSystem/DTOs/MoveItemDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class MoveItemDTO implements DTOInterface
{
    public function __construct(
        public int $target_folder_id,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(target_folder_id: (int) $data['target_folder_id']);
    }

    public function toArray(): array
    {
        return ['target_folder_id' => $this->target_folder_id];
    }
}
```

### 5.6 `RenameItemDTO`

`Modules/FileSystem/DTOs/RenameItemDTO.php`:

```php
<?php

namespace Modules\FileSystem\DTOs;

use App\Interfaces\DTOInterface;

readonly final class RenameItemDTO implements DTOInterface
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(name: $data['name']);
    }

    public function toArray(): array
    {
        return ['name' => $this->name];
    }
}
```

---

## المرحلة 6: إضافة الصلاحيات (Permissions) (~ 30 دقيقة)

تحديث `database/seeders/PermissionSeeder.php` — إضافة مجموعة `files`:

```php
'files' => ['list', 'show', 'create', 'edit', 'delete', 'move', 'rename', 'quota'],
```

**معانيها:**
- `list` / `show` — عرض المجلدات والملفات
- `create` / `edit` / `delete` — CRUD
- `move` — نقل عنصر
- `rename` — إعادة تسمية عنصر
- `quota` — عرض وترقية المساحة التخزينية

---

## المرحلة 7: التحقق (~ 1 ساعة)

1. `php artisan migrate` — يجب أن ينشئ الجداول الثلاثة بدون أخطاء.
2. `php artisan db:seed --class=PermissionSeeder` — يجب أن ينشئ صلاحيات `files.*`.
3. `composer pint` — تنسيق الكود.
4. تشغيل `php artisan tinker` وإنشاء مجلد وملف للتأكد من صحة الـ models والـ factories.

---

## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| المرحلة 1 + 3 | ServiceProvider و Enums مستقلان | لا شيء |
| المرحلة 2.1, 2.2, 2.3 | Migration واحدة للوحدة، لكن الجداول منفصلة | المرحلة 1 (وجود المجلد) |
| المرحلة 4.1, 4.2, 4.3 + Factories | كل model مستقل عن الآخر، factories تشير لأسماء فقط | المرحلة 3 (الـ Enums للتضمين) |
| المرحلة 5 (كل الـ DTOs) | كل DTO مستقل | لا شيء |
| المرحلة 6 | تحديث PermissionSeeder | لا شيء |

> **ملاحظة:** لا توجد خطّتان أخريان في المشروع تستطيع هذه الخطة أن تعمل بالتوازي معها — كل الخطط اللاحقة (#21 → #24) تعتمد على هذه.

---

## معايير القبول

- [ ] `php artisan migrate` ينشئ جداول `user_folders`, `user_files`, `storage_limits` بنجاح.
- [ ] جدول `user_folders` يحوي كل الأعمدة المذكورة في 2.1 + فهارس `['user_id', 'parent_id']` و `['source_type', 'source_id']` + قيد `unique_folder_name_per_parent`.
- [ ] جدول `user_files` يحوي كل الأعمدة المذكورة في 2.2 + فهارس `['user_id', 'folder_id']` و `['user_id', 'file_type']` + قيد `unique_file_name_per_folder`.
- [ ] جدول `storage_limits` يحوي `user_id UNIQUE` + `quota_bytes` بقيمة افتراضية 100 MB.
- [ ] `Modules\FileSystem\Enums\FileType` يحتوي على TEXT و IMAGE مع `allowedExtensions()` و `maxSizeBytes()`.
- [ ] `Modules\FileSystem\Enums\StoragePackageType` يحتوي على 5 أنواع مع `quotaBytes()` لكل نوع.
- [ ] `UserFolder` يرث من `BaseModel` ويعرّف `$filterableColumns`, `$searchableColumns`, `$dateFilterableColumns` ويحتوي على `isDescendantOf()` لمنع الحلقات.
- [ ] `UserFile` يرث من `BaseModel` ويعرّف `isText()` و `isImage()` + scopes.
- [ ] `StorageLimit` يعرّف `usedPercentage`, `remainingBytes`, `isExceeded`, `isNearLimit`, `hasAvailableSpace()`.
- [ ] 6 DTOs تطبّق `DTOInterface` مع `fromRequest()` و `toArray()`.
- [ ] `UserFolderFactory` يدعم `property()`, `general()`, `withParent()`.
- [ ] `UserFileFactory` يدعم `image()`, `emptyFile()`.
- [ ] `PermissionSeeder` يحوي مجموعة `files` بالصلاحيات الثمانية.
- [ ] `composer pint` يمر بدون أخطاء تنسيق.
- [ ] `FileSystemServiceProvider` يُسجَّل تلقائيًا ويُحمِّل الـ routes والـ migrations.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
