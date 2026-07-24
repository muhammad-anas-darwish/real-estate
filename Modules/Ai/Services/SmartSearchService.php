<?php

namespace Modules\Ai\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Modules\Core\SubModules\Location\Entities\City;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Enums\PropertyStatus;
use Modules\RealEstate\Enums\PropertyType;

class SmartSearchService extends BaseService
{
    public const CACHE_TAG = 'ai_search';

    public const CACHE_TTL = 86400;

    public const PER_PAGE = 20;

    public function __construct(
        protected readonly AiService $ai
    ) {}

    public function search(string $query, int $page = 1): array
    {
        $cacheKey = $this->cacheKey($query, $page);

        return Cache::tags([self::CACHE_TAG])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn () => $this->executeSearch($query, $page)
        );
    }

    protected function executeSearch(string $query, int $page): array
    {
        $language = $this->detectLanguage($query);
        $extracted = $this->extractRequirements($query, $language);

        if ($extracted === null) {
            return $this->fallbackSearch($query, $page);
        }

        $properties = $this->buildAndExecuteQuery($extracted, $page);
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
                // ignore
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
            ->limit(self::PER_PAGE * 3)
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
                    if ($p->property_type?->value === $extracted['property_type']) {
                        $score++;
                    }
                }
                if (! empty($extracted['rooms_min'])) {
                    $maxScore++;
                    if ($p->rooms >= (int) $extracted['rooms_min']) {
                        $score++;
                    }
                }
                if (! empty($extracted['area_min'])) {
                    $maxScore++;
                    if ($p->area >= (int) $extracted['area_min']) {
                        $score++;
                    }
                }
                if (! empty($extracted['price_max'])) {
                    $maxScore++;
                    if ($p->price <= (float) $extracted['price_max']) {
                        $score++;
                    }
                }
                if (! empty($extracted['city'])) {
                    $maxScore++;
                    if ($p->city && str_contains(strtolower($p->city->name), strtolower($extracted['city']))) {
                        $score++;
                    }
                }
                if (! empty($extracted['features'])) {
                    foreach ($extracted['features'] as $feature) {
                        $maxScore++;
                        $combined = strtolower($p->name.' '.$p->description);
                        if (str_contains($combined, strtolower($feature))) {
                            $score++;
                        }
                    }
                }

                $p->match_score = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
                $p->match_details = ['matched' => $score, 'total' => $maxScore];

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
