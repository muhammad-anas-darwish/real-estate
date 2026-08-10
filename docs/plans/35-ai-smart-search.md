# المهمة #35: البحث الذكي بالذكاء الاصطناعي (AI Smart Search)

> **التقرير المصدر:** `docs/reports/05-ai-smart-search-and-description.md` (السيناريوهات 1، 2، 6، القواعد 1–9، معايير القبول "البحث الذكي")
> **الهدف:** بناء SmartSearchService + endpoint + frontend guide. يستقبل نصًا طبيعيًا (عربي/إنجليزي) ويستخرج المتطلبات عبر Kimi، ثم يبحث ويفلتر ويرتّب.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟠 عالية
> **الجهد المقدّر:** 6–8 ساعات
> **الاعتمادية:** يجب أن يسبقها #34 (AI Foundation).
> **راجع:** `docs/reports/05-ai-smart-search-and-description.md`

---

## الوضع الحالي

بعد الخطة #34، `AiService` و 5 routes stubs موجودة. لكن لا يوجد منطق استخراج متطلبات + بحث + ترتيب.

هذه الخطة:
- `SmartSearchService` يستقبل النص → يستخرج JSON → يبني query → ينفّذ → يرتّب.
- Endpoint: `POST /api/ai/search`.
- Fallback ذكي للبحث التقليدي عند فشل الـ AI.
- اختبارات شاملة.
- Frontend guide.

---

## المرحلة 1: `SmartSearchService` (~ 3 ساعات)

`Modules/Ai/Services/SmartSearchService.php`:

```php
<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\TypeOfContract;
use Modules\Core\SubModules\Location\Entities\City;

class SmartSearchService extends BaseService
{
    public const CACHE_TTL = 86400;
    public const CACHE_TAG = 'ai_search';
    public const PER_PAGE = 20;

    public function __construct(
        protected readonly AiService $ai
    ) {}

    /**
     * Main search: text → requirements → properties.
     */
    public function search(string $query, int $page = 1): array
    {
        $cacheKey = $this->cacheKey($query, $page);

        return Cache::tags([self::CACHE_TAG])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->executeSearch($query, $page)
        );
    }

    protected function executeSearch(string $query, int $page): array
    {
        // Step 1: detect language
        $language = $this->detectLanguage($query);

        // Step 2: extract requirements via AI
        $extracted = $this->extractRequirements($query, $language);

        if ($extracted === null) {
            // Fallback: simple text search
            return $this->fallbackSearch($query, $page);
        }

        // Step 3: build query
        $properties = $this->buildAndExecuteQuery($extracted, $page);

        // Step 4: rank by match score
        $ranked = $this->rankByMatch($properties, $extracted);

        return [
            'properties' => $ranked,
            'extracted_requirements' => $extracted,
            'total_matching' => $ranked->count(),
            'returned' => min($ranked->count(), self::PER_PAGE),
            'language' => $language,
        ];
    }

    protected function detectLanguage(string $query): string
    {
        // Arabic chars range: \x{0600}-\x{06FF}
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $query)) {
            return 'ar';
        }
        return 'en';
    }

    protected function extractRequirements(string $query, string $language): ?array
    {
        if (! $this->ai->isAvailable()) {
            return null;
        }

        $systemPrompt = match ($language) {
            'ar' => 'أنت مساعد عقاري. استخرج من نص العميل المتطلبات التالية في JSON فقط بدون أي شرح. إذا لم تجد قيمة، اتركها null. المتطلبات: property_type (apartment/house/villa/land/commercial/office/warehouse), rooms_min (int), area_min (int), city (string), price_min (float), price_max (float), features (array of strings).',
            'en' => 'You are a real-estate assistant. Extract from the user text the following requirements in JSON only with no explanation. If a field is missing, set null. Fields: property_type (apartment/house/villa/land/commercial/office/warehouse), rooms_min (int), area_min (int), city (string), price_min (float), price_max (float), features (array of strings).',
        };

        return $this->ai->chatJson($systemPrompt, $query, ['temperature' => 0.1]);
    }

    protected function buildAndExecuteQuery(array $extracted, int $page)
    {
        $query = Property::query()
            ->where('status', PropertyStatus::APPROVED)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if (! empty($extracted['property_type'])) {
            try {
                $type = PropertyType::from($extracted['property_type']);
                $query->where('property_type', $type);
            } catch (\ValueError $e) {
                // ignore invalid type
            }
        }

        if (! empty($extracted['rooms_min'])) {
            $query->where('rooms', '>=', (int) $extracted['rooms_min']);
        }

        if (! empty($extracted['area_min'])) {
            $query->where('area', '>=', (int) $extracted['area_min']);
        }

        if (! empty($extracted['price_min'])) {
            $query->where('price', '>=', (float) $extracted['price_min']);
        }

        if (! empty($extracted['price_max'])) {
            $query->where('price', '<=', (float) $extracted['price_max']);
        }

        if (! empty($extracted['city'])) {
            $cityName = $extracted['city'];
            $city = City::where('name', 'LIKE', "%{$cityName}%")->first();
            if ($city) {
                $query->where('city_id', $city->id);
            }
        }

        // Features: search in name + description
        if (! empty($extracted['features']) && is_array($extracted['features'])) {
            $query->where(function ($q) use ($extracted) {
                foreach ($extracted['features'] as $feature) {
                    $q->orWhere('name', 'LIKE', "%{$feature}%")
                        ->orWhere('description', 'LIKE', "%{$feature}%");
                }
            });
        }

        return $query
            ->with('city:id,name')
            ->limit(self::PER_PAGE * 3)  // get more for ranking
            ->get();
    }

    protected function rankByMatch($properties, array $extracted): \Illuminate\Support\Collection
    {
        return $properties
            ->map(function ($p) use ($extracted) {
                $score = 0;
                $maxScore = 0;

                if (! empty($extracted['property_type'])) {
                    $maxScore++;
                    if ($p->property_type?->value === $extracted['property_type']) $score++;
                }
                if (! empty($extracted['rooms_min'])) {
                    $maxScore++;
                    if ($p->rooms >= (int) $extracted['rooms_min']) $score++;
                }
                if (! empty($extracted['area_min'])) {
                    $maxScore++;
                    if ($p->area >= (int) $extracted['area_min']) $score++;
                }
                if (! empty($extracted['price_max'])) {
                    $maxScore++;
                    if ($p->price <= (float) $extracted['price_max']) $score++;
                }
                if (! empty($extracted['city'])) {
                    $maxScore++;
                    if ($p->city && str_contains(strtolower($p->city->name), strtolower($extracted['city']))) $score++;
                }
                if (! empty($extracted['features'])) {
                    foreach ($extracted['features'] as $feature) {
                        $maxScore++;
                        $combined = strtolower($p->name.' '.$p->description);
                        if (str_contains($combined, strtolower($feature))) $score++;
                    }
                }

                $p->match_score = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
                $p->match_details = [
                    'matched' => $score,
                    'total' => $maxScore,
                ];

                return $p;
            })
            ->sortByDesc('match_score')
            ->take(self::PER_PAGE)
            ->values();
    }

    protected function fallbackSearch(string $query, int $page): array
    {
        $properties = Property::where('status', PropertyStatus::APPROVED)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->with('city:id,name')
            ->limit(self::PER_PAGE)
            ->offset(($page - 1) * self::PER_PAGE)
            ->get();

        return [
            'properties' => $properties,
            'extracted_requirements' => null,
            'total_matching' => $properties->count(),
            'returned' => $properties->count(),
            'language' => $this->detectLanguage($query),
            'fallback' => true,
        ];
    }

    protected function cacheKey(string $query, int $page): string
    {
        return 'search:'.md5($query.':'.$page);
    }
}
```

---

## المرحلة 2: `SmartSearchController` الكامل (~ 1 ساعة)

`Modules/Ai/Http/Controllers/SmartSearchController.php`:

```php
<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ai\Services\SmartSearchService;
use Modules\Ai\Http\Resources\SearchResultResource;

class SmartSearchController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected readonly SmartSearchService $search
    ) {}

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:5|max:500',
        ]);

        $query = trim($request->input('query'));
        $page = max(1, (int) $request->input('page', 1));

        $result = $this->search->search($query, $page);

        return $this->successResponse($result);
    }
}
```

> ملاحظة: لا يحتاج `applyPermissions` لأنه route public (لكن خلف throttle:ai-search).

---

## المرحلة 3: اختبارات (~ 2 ساعات)

`Modules/Ai/Tests/SmartSearchServiceTest.php`:

```php
<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Services\AiService;
use Modules\Ai\Services\SmartSearchService;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;
use Tests\TestCase;

class SmartSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);

        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id, 'name' => 'Riyadh']);
    }

    public function test_search_extracts_arabic_requirements(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'apartment',
                    'rooms_min' => 3,
                    'city' => 'Riyadh',
                    'price_max' => 500000,
                    'features' => ['قرب مدرسة'],
                ])]]],
            ], 200),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Apartment,
            'rooms' => 3, 'area' => 150, 'price' => 400000,
            'latitude' => 24.7, 'longitude' => 46.7,
            'city_id' => City::where('name', 'Riyadh')->first()->id,
        ]);
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Villa,
            'rooms' => 5, 'price' => 800000,
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('أريد شقة 3 غرف في الرياض أقل من 500 ألف');

        $this->assertEquals('ar', $result['language']);
        $this->assertNotNull($result['extracted_requirements']);
        $this->assertEquals('apartment', $result['extracted_requirements']['property_type']);
        $this->assertCount(1, $result['properties']);
    }

    public function test_search_detects_english(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'villa',
                ])]]],
            ], 200),
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('I want a villa in Jeddah');

        $this->assertEquals('en', $result['language']);
    }

    public function test_search_falls_back_on_ai_failure(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'server'], 500),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'شقة فاخرة',
            'description' => 'شقة قريبة من مدرسة',
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('شقة فاخرة');

        $this->assertTrue($result['fallback']);
        $this->assertNotEmpty($result['properties']);
    }

    public function test_search_falls_back_when_api_key_missing(): void
    {
        config(['services.kimi.api_key' => null]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'فيلا مع مسبح',
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('فيلا مع مسبح');

        $this->assertTrue($result['fallback']);
    }

    public function test_search_ranks_by_match_score(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'property_type' => 'apartment',
                    'rooms_min' => 3,
                    'city' => 'Riyadh',
                ])]]],
            ], 200),
        ]);

        $cityId = City::where('name', 'Riyadh')->first()->id;

        // 4/3 matches
        $a = Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Apartment,
            'rooms' => 3, 'city_id' => $cityId,
            'latitude' => 24.7, 'longitude' => 46.7,
        ]);
        // 2/3 matches
        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'property_type' => PropertyType::Villa,
            'rooms' => 3, 'city_id' => $cityId,
        ]);

        $service = new SmartSearchService(new AiService);
        $result = $service->search('شقة 3 غرف في الرياض');

        $this->assertEquals($a->id, $result['properties'][0]->id);
        $this->assertEquals(100, $result['properties'][0]->match_score);
    }

    public function test_search_caches_results(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '{}']]],
            ], 200),
        ]);

        $service = new SmartSearchService(new AiService);
        $service->search('شقة');
        $service->search('شقة');

        Http::assertSentCount(1);
    }
}
```

`Modules/Ai/Tests/SmartSearchControllerTest.php`:

```php
<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\Core\SubModules\Location\Entities\Country;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Tests\TestCase;

class SmartSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
        $country = Country::factory()->create();
        City::factory()->create(['country_id' => $country->id]);
    }

    public function test_search_endpoint_is_public(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '{}']]],
            ], 200),
        ]);

        $response = $this->postJson('/api/ai/search', ['query' => 'شقة في الرياض']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['properties', 'extracted_requirements', 'total_matching', 'returned', 'language']]);
    }

    public function test_search_validates_min_length(): void
    {
        $response = $this->postJson('/api/ai/search', ['query' => 'abc']);
        $response->assertStatus(422);
    }

    public function test_search_validates_max_length(): void
    {
        $response = $this->postJson('/api/ai/search', ['query' => str_repeat('a', 501)]);
        $response->assertStatus(422);
    }

    public function test_search_falls_back_on_ai_error(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response(['error' => 'server'], 500),
        ]);

        Property::factory()->create([
            'status' => PropertyStatus::APPROVED,
            'name' => 'فيلا فاخرة',
        ]);

        $response = $this->postJson('/api/ai/search', ['query' => 'فيلا فاخرة']);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.fallback'));
    }
}
```

---

## المرحلة 4: Frontend Guide (~ 1.5 ساعة)

`docs/frontend/ai-smart-search-frontend.md`:

```markdown
# دليل Frontend: البحث الذكي (AI Smart Search)

## مكون SmartSearchBar

```jsx
// components/SmartSearchBar.tsx
import { useState } from 'react';

export function SmartSearchBar({ onResults }) {
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSearch = async () => {
    if (query.length < 5) {
      toast.error('الرجاء كتابة 5 أحرف على الأقل');
      return;
    }
    if (query.length > 500) {
      toast.error('الرجاء اختصار النص إلى 500 حرف');
      return;
    }

    setLoading(true);
    try {
      const response = await fetch('/api/ai/search', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ query }),
      });
      const data = await response.json();
      onResults(data.data);
    } catch (err) {
      toast.error('حدث خطأ. يرجى المحاولة مرة أخرى.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="smart-search-bar">
      <input
        type="text"
        value={query}
        onChange={(e) => setQuery(e.target.value)}
        placeholder="صف ما تبحث عنه... (مثال: شقة 3 غرف في الرياض)"
        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
      />
      <button onClick={handleSearch} disabled={loading} className="ai-button">
        {loading ? '...' : 'بحث ذكي'}
      </button>
    </div>
  );
}
```

## شاشة النتائج

```jsx
// pages/AiSearchResults.tsx
import { useState } from 'react';

export function AiSearchResults({ results }) {
  const [page, setPage] = useState(1);

  return (
    <div className="ai-search-results">
      {/* Transparency Panel */}
      {results.extracted_requirements && (
        <div className="what-we-searched">
          <h3>ما الذي بحثت عنه:</h3>
          <ul>
            {results.extracted_requirements.property_type && (
              <li>نوع: {results.extracted_requirements.property_type}</li>
            )}
            {results.extracted_requirements.rooms_min && (
              <li>غرف: {results.extracted_requirements.rooms_min}+</li>
            )}
            {results.extracted_requirements.city && (
              <li>المدينة: {results.extracted_requirements.city}</li>
            )}
            {results.extracted_requirements.price_max && (
              <li>السعر: حتى {results.extracted_requirements.price_max}</li>
            )}
            {results.extracted_requirements.features?.length > 0 && (
              <li>ميزات: {results.extracted_requirements.features.join('، ')}</li>
            )}
          </ul>
          <button onClick={() => setShowFilters(true)}>تعديل الفلاتر</button>
        </div>
      )}

      {results.fallback && (
        <div className="fallback-notice">
          ℹ️ البحث الذكي غير متاح. تم استخدام البحث التقليدي.
        </div>
      )}

      <h2>{results.total_matching} عقار مطابق</h2>

      <div className="properties-grid">
        {results.properties.map((p) => (
          <PropertyCard key={p.id} property={p}>
            {p.match_score !== undefined && (
              <span className="match-score">{p.match_score}% تطابق</span>
            )}
          </PropertyCard>
        ))}
      </div>

      {results.total_matching > 20 && (
        <button onClick={() => loadMore()}>تحميل المزيد</button>
      )}
    </div>
  );
}
```

## Empty State

```jsx
{results.properties.length === 0 && (
  <div className="empty-state">
    <h3>لم نجد عقارات مطابقة</h3>
    <p>جرّب توسيع شروط البحث أو إزالة بعضها.</p>
    <Link href="/properties">تصفح كل العقارات</Link>
  </div>
)}
```

## Rate Limit Notice

```jsx
if (response.status === 429) {
  toast.error('تجاوزت حد البحث الذكي. حاول بعد ساعة.');
}
```

## Acceptance Checklist

- [ ] شريط بحث في الهيدر مع زر "بحث ذكي"
- [ ] Validation: 5-500 حرف
- [ ] Loading state أثناء الانتظار
- [ ] شاشة نتائج مع شفاف
- [ ] Match score % لكل عقار
- [ ] زر "تحميل المزيد" (pagination)
- [ ] Empty state عند 0 نتائج
- [ ] Fallback message عند فشل API
- [ ] Rate limit message عند 429
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Service) | مستقل | #34 |
| 2 (Controller) | يعتمد على 1 | 1 |
| 3 (Tests) | يعتمد على 1+2 | 1 + 2 |
| 4 (Frontend doc) | مستقل | 1 + 2 |

> **ملاحظة:** تعمل بالتوازي الكامل مع الخطة #36 (Description Assistant).

---

## معايير القبول

- [ ] `POST /api/ai/search` يقبل `query` (5-500 حرف) ويعيد نتائج.
- [ ] `SmartSearchService::search()` يستخرج requirements عبر Kimi.
- [ ] اكتشاف اللغة: عربي vs إنجليزي.
- [ ] Fallback للبحث التقليدي عند فشل AI.
- [ ] Properties مرتّبة حسب `match_score`.
- [ ] 24 ساعة cache.
- [ ] Validation: 422 على input غير صالح.
- [ ] 6+ tests في `SmartSearchServiceTest` تمر.
- [ ] 4 tests في `SmartSearchControllerTest` تمر.
- [ ] `docs/frontend/ai-smart-search-frontend.md` موجود.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
