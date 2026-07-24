# المهمة #36: مساعد وصف العقار بالذكاء الاصطناعي (AI Description Assistant)

> **التقرير المصدر:** `docs/reports/05-ai-smart-search-and-description.md` (السيناريوهات 3، 4، 5، القواعد 1، 11، 12، معايير القبول "مساعد الوصف")
> **الهدف:** بناء DescriptionAssistantService + 4 endpoints + frontend guide. ثلاثة أوضاع: Generate from scratch, Improve existing, Suggest title/features.
> **الحالة:** ✅ مكتمل
> **الأولوية:** 🟡 متوسطة
> **الجهد المقدّر:** 5–7 ساعات
> **الاعتمادية:** يجب أن يسبقها #34 (AI Foundation).
> **راجع:** `docs/reports/05-ai-smart-search-and-description.md`

---

## الوضع الحالي

بعد الخطة #34، `AiService` و 4 routes stubs موجودة. لكن لا يوجد منطق توليد/تحسين/اقتراح.

هذه الخطة:
- `DescriptionAssistantService` مع 3 modes.
- 4 endpoints (generate, improve, suggest-title, suggest-features).
- اختبارات شاملة.
- Frontend guide.

---

## المرحلة 1: `DescriptionAssistantService` (~ 2.5 ساعة)

`Modules/Ai/Services/DescriptionAssistantService.php`:

```php
<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;

class DescriptionAssistantService extends BaseService
{
    public function __construct(
        protected readonly AiService $ai
    ) {}

    /**
     * Mode 1: Generate a description from scratch based on property fields.
     */
    public function generate(array $data): ?string
    {
        if (! $this->ai->isAvailable()) {
            return null;
        }

        $language = $data['language'] ?? 'ar';
        $systemPrompt = $this->generateSystemPrompt($language);

        $userPrompt = $this->buildUserPrompt($data, $language, forGenerate: true);

        return $this->ai->chat($systemPrompt, $userPrompt, [
            'temperature' => 0.5,
            'max_tokens' => 800,
        ]);
    }

    /**
     * Mode 2: Improve an existing description.
     */
    public function improve(array $data): ?string
    {
        if (! $this->ai->isAvailable()) {
            return null;
        }

        if (empty($data['current_description']) || strlen($data['current_description']) < 10) {
            return null;
        }

        $language = $data['language'] ?? 'ar';
        $systemPrompt = $this->improveSystemPrompt($language);

        $userPrompt = "الوصف الحالي:\n{$data['current_description']}\n\nحسّن هذا الوصف مع الحفاظ على المعنى الأصلي. أضف كلمات تسويقية قوية، صحّح الأخطاء اللغوية، واجعله أكثر جاذبية للقارئ.";

        return $this->ai->chat($systemPrompt, $userPrompt, [
            'temperature' => 0.4,
            'max_tokens' => 800,
        ]);
    }

    /**
     * Mode 3: Suggest 3 attractive titles.
     */
    public function suggestTitles(array $data): ?array
    {
        if (! $this->ai->isAvailable()) {
            return null;
        }

        $language = $data['language'] ?? 'ar';
        $systemPrompt = match ($language) {
            'ar' => 'أنت كاتب عقارات محترف. اقترح 3 عناوين جذابة ومختصرة (5-8 كلمات) لعقار بناءً على المعلومات المُعطاة. أعد JSON array فقط بدون شرح.',
            'en' => 'You are a professional real-estate copywriter. Suggest 3 attractive and concise titles (5-8 words) for a property based on the info. Return a JSON array only with no explanation.',
        };

        $userPrompt = $this->buildUserPrompt($data, $language, forGenerate: false);
        $userPrompt .= "\n\nأعد فقط: [\"عنوان 1\", \"عنوان 2\", \"عنوان 3\"]";

        $content = $this->ai->chat($systemPrompt, $userPrompt, [
            'temperature' => 0.7,
            'max_tokens' => 200,
        ]);

        if ($content === null) {
            return null;
        }

        $titles = json_decode(trim($content), true);
        return is_array($titles) ? array_slice($titles, 0, 3) : null;
    }

    /**
     * Mode 4: Suggest additional features common for this property type.
     */
    public function suggestFeatures(array $data): ?array
    {
        if (! $this->ai->isAvailable()) {
            return null;
        }

        $language = $data['language'] ?? 'ar';
        $type = $data['property_type'] ?? 'apartment';

        $systemPrompt = match ($language) {
            'ar' => "أنت خبير عقاري. اقترح 8 مميزات شائعة لعقار من نوع {$type} في السوق. أعد JSON array فقط بأسماء الميزات بالعربية.",
            'en' => "You are a real-estate expert. Suggest 8 common features for a {$type}. Return a JSON array of feature names in English.",
        };

        $userPrompt = match ($language) {
            'ar' => "نوع العقار: {$type}. أعطني 8 مميزات شائعة يبحث عنها العملاء عادةً (مثل: مسبح، حديقة، مصعد، حارس، إلخ). أعد JSON array فقط: [\"ميزة 1\", \"ميزة 2\", ...]",
            'en' => "Property type: {$type}. Give me 8 commonly sought features. Return JSON array only: [\"feature 1\", \"feature 2\", ...]",
        };

        $content = $this->ai->chat($systemPrompt, $userPrompt, [
            'temperature' => 0.4,
            'max_tokens' => 300,
        ]);

        if ($content === null) {
            return null;
        }

        $features = json_decode(trim($content), true);
        return is_array($features) ? array_slice($features, 0, 12) : null;
    }

    protected function generateSystemPrompt(string $language): string
    {
        return match ($language) {
            'ar' => 'أنت كاتب عقارات محترف باللغة العربية. تكتب أوصافًا تسويقية جذابة، دقيقة، غير مبالغ فيها. تستخدم لغة فصحى. الوصف 3-5 فقرات (300-500 كلمة). تتجنب الكلمات الكاذبة والمضللة. لا تستخدم علامات تنصيص أو ترقيم غير ضروري.',
            'en' => 'You are a professional real-estate copywriter. Write attractive, accurate, non-exaggerated marketing descriptions. Use professional language. The description should be 3-5 paragraphs (300-500 words). Avoid false or misleading claims.',
        };
    }

    protected function improveSystemPrompt(string $language): string
    {
        return match ($language) {
            'ar' => 'أنت محرر نصوص محترف. حسّن الوصف المُعطى مع الحفاظ على المعنى الأصلي. أضف كلمات تسويقية، صحّح الأخطاء، واجعله أكثر جاذبية.',
            'en' => 'You are a professional editor. Improve the given description while keeping the original meaning. Add marketing language, fix errors, make it more attractive.',
        };
    }

    protected function buildUserPrompt(array $data, string $language, bool $forGenerate): string
    {
        $parts = [];
        $parts[] = "نوع العقار: ".($data['property_type'] ?? 'غير محدد');
        if (! empty($data['rooms'])) $parts[] = "عدد الغرف: {$data['rooms']}";
        if (! empty($data['bathrooms'])) $parts[] = "عدد دورات المياه: {$data['bathrooms']}";
        if (! empty($data['area'])) $parts[] = "المساحة: {$data['area']} متر مربع";
        if (! empty($data['city'])) $parts[] = "المدينة: {$data['city']}";
        if (! empty($data['price'])) $parts[] = "السعر: {$data['price']} ريال";
        if (! empty($data['features']) && is_array($data['features'])) {
            $parts[] = "الميزات الموجودة: ".implode('، ', $data['features']);
        }

        $header = $forGenerate
            ? "اكتب وصفًا تسويقيًا احترافيًا لهذا العقار:"
            : "معلومات العقار:";

        return $header."\n\n".implode("\n", $parts);
    }
}
```

---

## المرحلة 2: `DescriptionAssistantController` الكامل (~ 1 ساعة)

`Modules/Ai/Http/Controllers/DescriptionAssistantController.php`:

```php
<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponses;
use App\Traits\ApplyPermissions;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ai\Services\DescriptionAssistantService;

class DescriptionAssistantController extends Controller
{
    use ApiResponses, ApplyPermissions, ValidatesRequests;

    public function __construct(
        protected readonly DescriptionAssistantService $assistant
    ) {
        $this->applyPermissions(
            'properties',
            [],
            [
                'generate' => 'create',
                'improve' => 'edit',
                'suggestTitle' => 'create',
                'suggestFeatures' => 'create',
            ]
        );
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request);

        $text = $this->assistant->generate($data);

        if ($text === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة حاليًا.', 503);
        }

        return $this->successResponse([
            'description' => trim($text),
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function improve(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request, requireCurrent: true);

        $text = $this->assistant->improve($data);

        if ($text === null) {
            return $this->failedResponse('تعذّر التحسين. النص قصير جدًا أو الخدمة غير متاحة.', 422);
        }

        return $this->successResponse([
            'description' => trim($text),
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function suggestTitle(Request $request): JsonResponse
    {
        $data = $this->validateRequest($request);

        $titles = $this->assistant->suggestTitles($data);

        if ($titles === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة.', 503);
        }

        return $this->successResponse([
            'titles' => $titles,
            'language' => $data['language'] ?? 'ar',
        ]);
    }

    public function suggestFeatures(Request $request): JsonResponse
    {
        $request->validate([
            'property_type' => 'required|string',
            'language' => 'nullable|in:ar,en',
        ]);

        $features = $this->assistant->suggestFeatures($request->all());

        if ($features === null) {
            return $this->failedResponse('خدمة الذكاء الاصطناعي غير متاحة.', 503);
        }

        return $this->successResponse([
            'features' => $features,
            'language' => $request->input('language', 'ar'),
        ]);
    }

    protected function validateRequest(Request $request, bool $requireCurrent = false): array
    {
        $rules = [
            'property_type' => 'nullable|string',
            'rooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'area' => 'nullable|integer|min:0',
            'city' => 'nullable|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string|max:100',
            'current_description' => $requireCurrent ? 'required|string|min:10|max:2000' : 'nullable|string|max:2000',
            'language' => 'nullable|in:ar,en',
        ];

        return $this->validate($request, $rules);
    }
}
```

---

## المرحلة 3: اختبارات (~ 2 ساعة)

`Modules/Ai/Tests/DescriptionAssistantServiceTest.php`:

```php
<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Ai\Services\AiService;
use Modules\Ai\Services\DescriptionAssistantService;
use Tests\TestCase;

class DescriptionAssistantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);
    }

    public function test_generate_returns_description(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'فيلا فاخرة في حي الياسمين...']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $result = $service->generate([
            'property_type' => 'villa',
            'rooms' => 5, 'area' => 400, 'city' => 'Riyadh',
        ]);

        $this->assertStringContainsString('فيلا فاخرة', $result);
    }

    public function test_generate_returns_null_when_api_unavailable(): void
    {
        config(['services.kimi.api_key' => null]);

        $service = new DescriptionAssistantService(new AiService);
        $this->assertNull($service->generate(['property_type' => 'villa']));
    }

    public function test_improve_returns_improved_text(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'الوصف المحسّن: فيلا مميزة...']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $result = $service->improve([
            'current_description' => 'فيلا 5 غرف في الرياض',
        ]);

        $this->assertStringContainsString('فيلا مميزة', $result);
    }

    public function test_improve_returns_null_for_short_text(): void
    {
        $service = new DescriptionAssistantService(new AiService);
        $this->assertNull($service->improve(['current_description' => 'قصير']));
    }

    public function test_suggest_titles_returns_array(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["فيلا الأحلام", "بيت العائلة", "عش الرفاهية"]']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $titles = $service->suggestTitles(['property_type' => 'villa', 'rooms' => 5]);

        $this->assertCount(3, $titles);
        $this->assertEquals('فيلا الأحلام', $titles[0]);
    }

    public function test_suggest_features_returns_array(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["مسبح", "حديقة", "موقف سيارات", "مصعد", "حارس", "نظام أمان", "إطلالة", "مكيف مركزي"]']]],
            ], 200),
        ]);

        $service = new DescriptionAssistantService(new AiService);
        $features = $service->suggestFeatures(['property_type' => 'villa']);

        $this->assertCount(8, $features);
        $this->assertEquals('مسبح', $features[0]);
    }
}
```

`Modules/Ai/Tests/DescriptionAssistantControllerTest.php`:

```php
<?php

namespace Modules\Ai\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DescriptionAssistantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $trader;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.kimi.api_key' => 'test_key']);

        Permission::firstOrCreate(['name' => 'properties.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'properties.edit', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'trader', 'guard_name' => 'web']);
        $role->givePermissionTo(['properties.create', 'properties.edit']);

        $this->trader = User::factory()->create();
        $this->trader->assignRole('trader');
    }

    public function test_generate_endpoint_requires_auth(): void
    {
        $response = $this->postJson('/api/ai/description/generate', ['property_type' => 'villa']);
        $response->assertStatus(401);
    }

    public function test_generate_endpoint_works_for_trader(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => 'فيلا فاخرة']]],
            ], 200),
        ]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/generate', [
                'property_type' => 'villa',
                'rooms' => 5,
                'city' => 'Riyadh',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('فيلا فاخرة', $response->json('data.description'));
    }

    public function test_generate_returns_503_when_ai_unavailable(): void
    {
        config(['services.kimi.api_key' => null]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/generate', ['property_type' => 'villa']);

        $response->assertStatus(503);
    }

    public function test_improve_validates_current_description_required(): void
    {
        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/improve', []);

        $response->assertStatus(422);
    }

    public function test_suggest_title_returns_three_titles(): void
    {
        Http::fake([
            'api.moonshot.cn/*' => Http::response([
                'choices' => [['message' => ['content' => '["عنوان 1", "عنوان 2", "عنوان 3"]']]],
            ], 200),
        ]);

        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/suggest-title', [
                'property_type' => 'villa', 'rooms' => 5,
            ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.titles'));
    }

    public function test_suggest_features_requires_property_type(): void
    {
        $response = $this->actingAs($this->trader)
            ->postJson('/api/ai/description/suggest-features', []);

        $response->assertStatus(422);
    }

    public function test_non_trader_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/ai/description/generate', ['property_type' => 'villa']);

        $response->assertStatus(403);
    }
}
```

---

## المرحلة 4: Frontend Guide (~ 1 ساعة)

`docs/frontend/ai-description-assistant-frontend.md`:

```markdown
# دليل Frontend: مساعد وصف العقار

## في نموذج إنشاء/تعديل العقار

```jsx
// components/PropertyDescriptionAssistant.tsx
import { useState } from 'react';

export function PropertyDescriptionAssistant({ formData, onApply }) {
  const [loading, setLoading] = useState(false);
  const [mode, setMode] = useState(null); // 'generate' | 'improve' | 'title' | 'features'
  const [suggestions, setSuggestions] = useState(null);

  const callAI = async (endpoint, body) => {
    setLoading(true);
    setMode(endpoint);
    try {
      const response = await fetch(`/api/ai/description/${endpoint}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${getToken()}`,
        },
        body: JSON.stringify(body),
      });

      if (response.status === 429) {
        toast.error('تجاوزت حد الاستخدام. حاول بعد ساعة.');
        return;
      }
      if (response.status === 503) {
        toast.error('خدمة الذكاء الاصطناعي غير متاحة.');
        return;
      }
      if (!response.ok) throw new Error('AI error');

      const data = await response.json();
      setSuggestions(data.data);
    } catch (err) {
      toast.error('حدث خطأ. حاول مرة أخرى.');
    } finally {
      setLoading(false);
    }
  };

  const handleGenerate = () => {
    callAI('generate', {
      property_type: formData.type,
      rooms: formData.rooms,
      bathrooms: formData.bathrooms,
      area: formData.area,
      city: formData.city,
      price: formData.price,
      features: formData.features,
      language: formData.language ?? 'ar',
    });
  };

  const handleImprove = () => {
    if (!formData.description || formData.description.length < 10) {
      toast.error('الوصف قصير جدًا. اكتب نصًا أطول أولاً.');
      return;
    }
    callAI('improve', {
      current_description: formData.description,
      language: formData.language ?? 'ar',
    });
  };

  const handleSuggestTitle = () => {
    callAI('suggest-title', {
      property_type: formData.type,
      rooms: formData.rooms,
      city: formData.city,
      language: formData.language ?? 'ar',
    });
  };

  const handleSuggestFeatures = () => {
    callAI('suggest-features', {
      property_type: formData.type,
      language: formData.language ?? 'ar',
    });
  };

  return (
    <div className="ai-assistant">
      <div className="assistant-buttons">
        <button onClick={handleGenerate} disabled={loading}>
          ✨ اقترح وصف
        </button>
        <button onClick={handleImprove} disabled={loading || !formData.description}>
          🔧 حسّن النص
        </button>
        <button onClick={handleSuggestTitle} disabled={loading}>
          💡 اقترح عنوان
        </button>
        <button onClick={handleSuggestFeatures} disabled={loading}>
          ➕ اقترح مميزات
        </button>
      </div>

      {loading && <Spinner />}

      {suggestions && mode === 'generate' && (
        <SuggestionModal
          title="الوصف المقترح"
          content={suggestions.description}
          onApply={(text) => onApply('description', text)}
          onRegenerate={handleGenerate}
        />
      )}

      {suggestions && mode === 'improve' && (
        <SuggestionModal
          title="الوصف المحسّن"
          content={suggestions.description}
          onApply={(text) => onApply('description', text)}
          onRegenerate={handleImprove}
        />
      )}

      {suggestions && mode === 'title' && (
        <TitleSuggestions
          titles={suggestions.titles}
          onApply={(title) => onApply('name', title)}
        />
      )}

      {suggestions && mode === 'features' && (
        <FeatureCheckboxes
          features={suggestions.features}
          selected={formData.features}
          onChange={(features) => onApply('features', features)}
        />
      )}
    </div>
  );
}
```

## Modals

```jsx
function SuggestionModal({ title, content, onApply, onRegenerate }) {
  return (
    <div className="modal">
      <h3>{title}</h3>
      <textarea readOnly defaultValue={content} rows={10} />
      <div className="modal-actions">
        <button onClick={() => onApply(content)} className="btn-primary">
          تطبيق
        </button>
        <button onClick={onRegenerate}>إعادة التوليد</button>
        <button onClick={onClose}>إلغاء</button>
      </div>
    </div>
  );
}

function TitleSuggestions({ titles, onApply }) {
  return (
    <div className="modal">
      <h3>اقتراحات العنوان</h3>
      {titles.map((title, i) => (
        <button key={i} onClick={() => onApply(title)} className="title-suggestion">
          {title}
        </button>
      ))}
    </div>
  );
}

function FeatureCheckboxes({ features, selected, onChange }) {
  return (
    <div className="modal">
      <h3>الميزات المقترحة</h3>
      {features.map((feature) => (
        <label key={feature}>
          <input
            type="checkbox"
            checked={selected.includes(feature)}
            onChange={(e) => {
              const next = e.target.checked
                ? [...selected, feature]
                : selected.filter((f) => f !== feature);
              onChange(next);
            }}
          />
          {feature}
        </label>
      ))}
    </div>
  );
}
```

## Acceptance Checklist

- [ ] 4 أزرار في نموذج العقار
- [ ] Modal لكل وضع
- [ ] زر "تطبيق" يحدّث الحقل
- [ ] زر "إعادة التوليد" يستدعي الـ AI مجدداً
- [ ] spinner أثناء التحميل
- [ ] رسائل خطأ واضحة (503, 429, 422)
- [ ] زر "حسّن" معطل إذا الوصف فارغ
- [ ] checkboxes للميزات (متعدد الاختيار)
```

---

## المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| 1 (Service) | مستقل | #34 |
| 2 (Controller) | يعتمد على 1 | 1 |
| 3 (Tests) | يعتمد على 1+2 | 1 + 2 |
| 4 (Frontend doc) | مستقل | 1 + 2 |

> **ملاحظة:** تعمل بالتوازي الكامل مع الخطة #35.

---

## معايير القبول

- [ ] `DescriptionAssistantService::generate()` ينشئ وصفًا من الحقول.
- [ ] `DescriptionAssistantService::improve()` يحسّن نصًا موجودًا (10+ حرف).
- [ ] `suggestTitles()` يُعيد 3 عناوين.
- [ ] `suggestFeatures()` يُعيد 8+ ميزات.
- [ ] 4 endpoints تحت `/api/ai/description/*` يعمل.
- [ ] كل endpoint محمي بـ auth + `properties.create/edit` permission.
- [ ] fallback: 503 عند عدم توفر AI.
- [ ] 6+ tests في `DescriptionAssistantServiceTest` تمر.
- [ ] 7 tests في `DescriptionAssistantControllerTest` تمر.
- [ ] `docs/frontend/ai-description-assistant-frontend.md` موجود.
- [ ] `composer pint` يمر.

---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
