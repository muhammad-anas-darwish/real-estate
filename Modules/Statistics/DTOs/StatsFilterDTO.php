<?php

namespace Modules\Statistics\DTOs;

use App\Interfaces\DTOInterface;
use Modules\Statistics\Enums\StatsPeriod;
use Modules\Statistics\ValueObjects\DateRange;

final readonly class StatsFilterDTO implements DTOInterface
{
    public function __construct(
        public StatsPeriod $period,
        public DateRange $range,
        public ?DateRange $previousRange = null,
        public ?int $cityId = null,
        public ?int $countryId = null,
        public ?int $categoryId = null,
        public ?int $publisherId = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        $periodValue = $data['period'] ?? StatsPeriod::LAST_30_DAYS->value;
        $period = StatsPeriod::from($periodValue);

        $range = $period === StatsPeriod::CUSTOM
            ? DateRange::custom($data['from'], $data['to'])
            : DateRange::fromPeriod($period);

        $previousRange = $period->supportsComparison()
            ? $range->previousRange()
            : null;

        return new self(
            period: $period,
            range: $range,
            previousRange: $previousRange,
            cityId: isset($data['city_id']) ? (int) $data['city_id'] : null,
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            publisherId: isset($data['publisher_id']) ? (int) $data['publisher_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'period' => $this->period->value,
            'range' => $this->range->toArray(),
            'previous_range' => $this->previousRange?->toArray(),
            'city_id' => $this->cityId,
            'country_id' => $this->countryId,
            'category_id' => $this->categoryId,
            'publisher_id' => $this->publisherId,
        ];
    }

    public function days(): int
    {
        return $this->range->days();
    }
}
