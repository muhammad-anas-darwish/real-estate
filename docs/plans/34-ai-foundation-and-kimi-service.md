# المهمة #34: تأسيس خدمة الذكاء الاصطناعي (AI Foundation + Kimi Service)

> **التقرير المصدر:** `docs/reports/05-ai-smart-search-and-description.md` (القواعد 1، 10، 13–17، معايير القبول "عام")
> **الهدف:** إنشاء `AiService` (يستخدم Kimi API) + Configuration + Cache + Rate Limiting + Tests. هذه البنية التحتية مشتركة بين البحث الذكي (#35) ومساعد الوصف (#36).
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 4–6 ساعات
> **الاعتمادية:** لا شيء — هذه الخطة هي الأساس.
> **راجع:** `docs/reports/05-ai-smart-search-and-description.md`

---

## الوضع الحالي

لا يوجد أي تكامل ذكاء اصطناعي في النظام. نحتاج تأسيس:
- `AiService` (wrapper حول Kimi API).
- Configuration: `KIMI_API_KEY`, `KIMI_MODEL`, base URL.
- Cache layer (24 ساعة للبحث، لا كاش للوصف).
- Rate Limiting (30/hour للبحث، 20/hour للوصف).
- Privacy: تشفير logs، عدم حفظ نصوص بشكل دائم.
- اختبارات شاملة (مع HTTP fake).

---

## المرحلة 1: Configuration (~ 30 دقيقة)

### 1.1 `config/services.php` — أضف Kimi block

في `config/services.php`:
```php
'kimi' => [
    'api_key' => env('KIMI_API_KEY'),
    'base_url' => env('KIMI_API_BASE_URL', 'https://api.moonshot.cn/v1'),
    'model' => env('KIMI_MODEL', 'moonshot-v1-8k'),
    'timeout' => env('KIMI_TIMEOUT', 30),
    'max_retries' => env('KIMI_MAX_RETRIES', 2),
],
```

### 1.2 `.env.example` — أضف
```
# Kimi AI (Moonshot)
KIMI_API_KEY=
KIMI_API_BASE_URL=https://api.moonshot.cn/v1
KIMI_MODEL=moonshot-v1-8k
KIMI_TIMEOUT=30
KIMI_MAX_RETRIES=2
```

---

## المرحلة 2: `AiService` (~ 3 ساعات)

`Modules/Ai/Services/AiService.php`:

```php
<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService extends BaseService
{
    /**
     * Generic method: send a prompt, get text response.
     */
    public function chat(string $systemPrompt, string $userPrompt, array $options = []): ?string
    {
        $apiKey = config('services.kimi.api_key');
        $baseUrl = config('services.kimi.base_url');
        $model = config('services.kimi.model');
        $timeout = config('services.kimi.timeout', 30);
        $maxRetries = config('services.kimi.max_retries', 2);

        if (! $apiKey) {
            Log::warning('KIMI_API_KEY not set. AI features disabled.');
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $options['temperature'] ?? 0.3,
            'max_tokens' => $options['max_tokens'] ?? 1000,
        ];

        $attempt = 0;
        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout($timeout)
                    ->withToken($apiKey)
                    ->post("{$baseUrl}/chat/completions", $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['choices'][0]['message']['content'] ?? null;
                }

                Log::warning('Kimi API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempt + 1,
                ]);

                if ($response->status() === 401 || $response->status() === 400) {
                    return null;
                }
            } catch (\Throwable $e) {
                Log::error('Kimi API exception: '.$e->getMessage());
            }

            $attempt++;
            if ($attempt <= $maxRetries) {
                sleep(pow(2, $attempt));
            }
        }

        return null;
    }

    /**
     * Send prompt, expect JSON response. Returns parsed array or null.
     */
    public function chatJson(string $systemPrompt, string $userPrompt, array $options = []): ?array
    {
        $content = $this->chat($systemPrompt, $userPrompt, $options);

        if ($content === null) {
            return null;
        }

        return $this->extractJson($content);
    }

    /**
     * Extract JSON from a response that may contain code fences.
     */
    protected function extractJson(string $content): ?array
    {
        $content = trim($content);

        // Strip ```json ... ``` fences
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function isAvailable(): bool
    {
        return ! empty(config('services.kimi.api_key'));
    }
}
```

---

## المرحلة 3: Rate Limiters (~ 30 دقيقة)

أضف في `App\Providers/AppServiceProvider::configureRateLimiters()`:

```php
RateLimiter::for('ai-search', function (Request $request) {
    return Limit::perHour(30)->by($request->ip());
});

RateLimiter::for('ai-description', function (Request $request) {
    return Limit::perHour(20)->by($request->user()?->id ?? $request->ip());
});
```

---

## المرحلة 4: هيكل الوحدة (~ 30 دقيقة)

```
Modules/Ai/
├── Providers/
│   └── AiServiceProvider.php
├── Services/
│   ├── AiService.php
│   ├── SmartSearchService.php        (stub for #35)
│   └── DescriptionAssistantService.php  (stub for #36)
├── DTOs/
│   ├── ExtractedRequirements.php      (stub for #35)
│   ├── SearchResult.php              (stub for #35)
│   └── DescriptionRequest.php        (stub for #36)
├── Http/
│   ├── Controllers/
│   │   ├── SmartSearchController.php  (stub for #35)
│   │   └── DescriptionAssistantController.php  (stub for #36)
│   └── Resources/
│       └── SearchResultResource.php   (stub for #35)
├── Routes/
│   └── api.php
└── Tests/
    ├── AiServiceTest.php
    ├── SmartSearchServiceTest.php     (stub for #35)
    └── DescriptionAssistantServiceTest.php  (stub for #36)
```

### 4.1 `Modules/Ai/Providers/AiServiceProvider.php`

```php
<?php

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }
}
```

### 4.2 `Modules/Ai/Routes/api.php` (skeleton فقط في هذه الخطة)

```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\SmartSearchController;
use Modules\Ai\Http\Controllers\DescriptionAssistantController;

Route::middleware('throttle:ai-search')->group(function () {
    Route::post('ai/search', [SmartSearchController::class, 'search']);
});

Route::middleware(['auth:sanctum', 'throttle:ai-description'])->group(function () {
    Route::post('ai/description/generate', [DescriptionAssistantController::class, 'generate']);
    Route::post('ai/description/improve', [DescriptionAssistantController::class, 'improve']);
    Route::post('ai/description/suggest-title', [DescriptionAssistantController::class, 'suggestTitle']);
    Route::post('ai/description/suggest-features', [DescriptionAssistantController::class, 'suggestFeatures']);
});
```

### 4.3 Service stubs

```php
<?php
// Modules/Ai/Services/SmartSearchService.php
namespace Modules\Ai\Services;
class SmartSearchService
{
    public const CACHE_TAG = 'ai_search';
    public const CACHE_TTL = 86400;
    // Filled in plan #35
}
```

```php
<?php
// Modules/Ai/Services/DescriptionAssistantService.php
namespace Modules\Ai\Services;
class DescriptionAssistantService
{
    // Filled in plan #36
}
```

### 4.4 Controller stubs (تعيد 501)

```php
<?php
// Modules/Ai/Http/Controllers/SmartSearchController.php
namespace Modules\Ai\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
class SmartSearchController extends Controller
{
    public function search(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Not Implemented — see plan #35'], 501);
    }
}
```

```php
<?php
// Modules/Ai/Http/Controllers/DescriptionAssistantController.php
namespace Modules\Ai\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
class DescriptionAssistantController extends Controller
{
    public function generate(): JsonResponse { return $this->notImpl('generate', '#36'); }
    public function improve(): JsonResponse { return $this->notImpl('improve', '#36'); }
    public function suggestTitle(): JsonResponse { return $this->notImpl('suggest-title', '#36'); }
    public function suggestFeatures(): JsonResponse { return $this->notImpl('suggest-features', '#36'); }
    private function notImpl(string $name, string $plan): JsonResponse
    {
        return response()->json(['success' => false, 'message' => "Not Implemented — see plan $plan"], 501);
    }
}
```

### 4.5 DTO stubs

```php
<?php
// Modules/Ai/DTOs/ExtractedRequirements.php
namespace Modules\Ai\DTOs;
use App\Interfaces\DTOInterface;
readonly final class ExtractedRequirements implements DTOInterface
{
    public function __construct(
        public ?string $propertyType = null,
        public ?int $roomsMin = null,
        public ?int $areaMin = null,
        public ?string $city = null,
        public ?float $priceMin = null,
        public ?float $priceMax = null,
        public array $features = [],
        public string $language = 'ar',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            propertyType: $data['property_type'] ?? null,
            roomsMin: isset($data['rooms_min']) ? (int) $data['rooms_min'] : null,
            areaMin: isset($data['area_min']) ? (int) $data['area_min'] : null,
            city: $data['city'] ?? null,
            priceMin: isset($data['price_min']) ? (float) $data['price_min'] : null,
            priceMax: isset($data['price_max']) ? (float) $data['price_max'] : null,
            features: $data['features'] ?? [],
            language: $data['language'] ?? 'ar',
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType,
            'rooms_min' => $this->roomsMin,
            'area_min' => $this->areaMin,
            'city' => $this->city,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
            'features' => $this->features,
            'language' => $this->language,
        ];
    }
}
```

```php
<?php
// Modules/Ai/DTOs/SearchResult.php
namespace Modules\Ai\DTOs;
use App\Interfaces\DTOInterface;
readonly final class SearchResult implements DTOInterface
{
    public function __construct(
        public array $properties,
        public array $extractedRequirements,
        public int $totalMatching,
        public int $returned,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            properties: $data['properties'] ?? [],
            extractedRequirements: $data['extracted_requirements'] ?? [],
            totalMatching: (int) ($data['total_matching'] ?? 0),
            returned: (int) ($data['returned'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'properties' => $this->properties,
            'extracted_requirements' => $this->extractedRequirements,
            'total_matching' => $this->totalMatching,
            'returned' => $this->returned,
        ];
    }
}
```

```php
<?php
// Modules/Ai/DTOs/DescriptionRequest.php
namespace Modules\Ai\DTOs;
use App\Interfaces\DTOInterface;
readonly final class DescriptionRequest implements DTOInterface
{
    public function __construct(
        public ?string $propertyType = null,
        public ?int $rooms = null,
        public ?int $bathrooms = null,
        public ?int $area = null,
        public ?string $city = null,
        public ?float $price = null,
        public array $features = [],
        public ?string $currentDescription = null,
        public string $language = 'ar',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            propertyType: $data['property_type'] ?? null,
            rooms: isset($data['rooms']) ? (int) $data['rooms'] : null,
            bathrooms: isset($data['bathrooms']) ? (int) $data['bathrooms'] : null,
            area: isset($data['area']) ? (int) $data['area'] : null,
            city: $data['city'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            features: $data['features'] ?? [],
            currentDescription: $data['current_description'] ?? null,
            language: $data['language'] ?? 'ar',
        );
    }

    public function toArray(): array
    {
        return [
            'property_type' => $this->propertyType,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'city' => $this->city,
            'price' => $this->price,
            'features' => $this->features,
            'current_description' => $this->currentDescription,
            'language' => $this->language,
        ];
    }
}
```

### 4.6 Resource stub

```php
<?php
// Modules/Ai/Http/Resources/SearchResultResource.php
namespace Modules\Ai\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class SearchResultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'properties' => $this->resource['properties'] ?? [],
            'extracted_requirements' => $this->resource['extracted_requirements'] ?? [],
            'meta' => [
                'total_matching' => $this->resource['total_matching'] ?? 0,
                'returned' => $this->resource['returned'] ?? 0,
                'is_truncated' => ($this->resource['total_matching'] ?? 0) > 20,
            ],
        ];
    }
}
```

### 4.7 تسجيل الـ ServiceProvider

في `bootstrap/providers.php`:
```php
Modules\Ai\Providers\AiServiceProvider::class,
```

---

## المرحلة 5: اختبارات (~ 1.5 ساعة)

`Modules/Ai/Tests/AiServiceTest.php`:

```php
<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Services\AiService;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
    }

    public function test_chat_returns_null_when_api_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);
        $service = new AiService;
        $this->assertNull($service->chat('system', 'user'));
    }

    public function test_chat_returns_content_on_success(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'مرحبا']]],
            ], 200),
        ]);

        $service = new AiService;
        $result = $service->chat('أنت مساعد', 'مرحبا');

        $this->assertEquals('مرحبا', $result);
    }

    public function test_chat_returns_null_on_401(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $service = new AiService;
        $this->assertNull($service->chat('system', 'user'));
    }

    public function test_chat_retries_on_500_then_succeeds(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::sequence()
                ->push(['error' => 'server'], 500)
                ->push(['choices' => [['message' => ['content' => 'ok']]]], 200),
        ]);

        $service = new AiService;
        $this->assertEquals('ok', $service->chat('s', 'u'));
        Http::assertSentCount(2);
    }

    public function test_chat_json_extracts_from_code_fence(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => [
                    'content' => '```json'."\n".'{"type":"apartment","rooms":3}'."\n".'```',
                ]]],
            ], 200),
        ]);

        $service = new AiService;
        $result = $service->chatJson('s', 'u');

        $this->assertEquals('apartment', $result['type']);
        $this->assertEquals(3, $result['rooms']);
    }

    public function test_chat_json_returns_null_on_invalid_json(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json']]],
            ], 200),
        ]);

        $service = new AiService;
        $this->assertNull($service->chatJson('s', 'u'));
    }

    public function test_is_available_returns_true_when_key_set(): void
    {
        $service = new AiService;
        $this->assertTrue($service->isAvailable());
    }

    public function test_is_available_returns_false_when_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);
        $service = new AiService;
        $this->assertFalse($service->isAvailable());
    }

    public function test_routes_are_registered(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->map(fn($r) => $r->uri())
            ->toArray();

        $this->assertContains('api/ai/search', $routes);
        $this->assertContains('api/ai/description/generate', $routes);
        $this->assertContains('api/ai/description/improve', $routes);
        $this->assertContains('api/ai/description/suggest-title', $routes);
        $this->assertContains('api/ai/description/suggest-features', $routes);
    }
}
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Config) | مستقل | لا شيء |
| 2 (AiService) | مستقل | لا شيء |
| 3 (RateLimiters) | مستقل | لا شيء |
| 4 (Module structure) | يعتمد على 1+2 | 1 + 2 |
| 5 (Tests) | يعتمد على 2+4 | 2 + 4 |

---

## معايير القبول

- [ ] `config/services.php` يحوي `kimi` block.
- [ ] `.env.example` يحوي `KIMI_API_KEY=`.
- [ ] `AiService::chat()` يُرسل prompt ويُرجع نص.
- [ ] `AiService::chatJson()` يستخرج JSON من code fences.
- [ ] `AiService::isAvailable()` يكشف المفتاح.
- [ ] retry logic: محاولتين عند 500+.
- [ ] Rate limiters: `ai-search` (30/hour/IP), `ai-description` (20/hour/user).
- [ ] 5 routes مسجّلة تحت `/api/ai/*`.
- [ ] `AiServiceProvider` مُسجّل في `bootstrap/providers.php`.
- [ ] 9+ tests في `AiServiceTest` تمر.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
