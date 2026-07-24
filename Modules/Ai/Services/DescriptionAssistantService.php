<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;

class DescriptionAssistantService extends BaseService
{
    public function __construct(
        protected readonly AiService $ai
    ) {}

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
            'ar' => 'أنت كاتب عقارات محترف باللغة العربية. تكتب أوصافًا تسويقية جذابة، دقيقة، غير مبالغ فيها. تستخدم لغة فصحى. الوصف 3-5 فقرات (300-500 كلمة). تتجنب الكلمات الكاذبة والمضللة.',
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
        $parts[] = 'نوع العقار: '.($data['property_type'] ?? 'غير محدد');
        if (! empty($data['rooms'])) {
            $parts[] = "عدد الغرف: {$data['rooms']}";
        }
        if (! empty($data['bathrooms'])) {
            $parts[] = "عدد دورات المياه: {$data['bathrooms']}";
        }
        if (! empty($data['area'])) {
            $parts[] = "المساحة: {$data['area']} متر مربع";
        }
        if (! empty($data['city'])) {
            $parts[] = "المدينة: {$data['city']}";
        }
        if (! empty($data['price'])) {
            $parts[] = "السعر: {$data['price']} ريال";
        }
        if (! empty($data['features']) && is_array($data['features'])) {
            $parts[] = 'الميزات الموجودة: '.implode('، ', $data['features']);
        }

        $header = $forGenerate
            ? 'اكتب وصفًا تسويقيًا احترافيًا لهذا العقار:'
            : 'معلومات العقار:';

        return $header."\n\n".implode("\n", $parts);
    }
}
