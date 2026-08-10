<?php

namespace Modules\Statistics\ValueObjects;

use Carbon\CarbonImmutable;
use Modules\Statistics\Enums\StatsPeriod;

final readonly class DateRange
{
    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
    ) {
        if ($from->gt($to)) {
            throw new \InvalidArgumentException('DateRange: from must be <= to');
        }
    }

    public function days(): int
    {
        return max(1, (int) $this->from->diffInDays($this->to) + 1);
    }

    public function previousRange(): self
    {
        $diff = $this->days();

        return new self(
            $this->from->subDays($diff),
            $this->to->subDays($diff),
        );
    }

    public function contains(CarbonImmutable $date): bool
    {
        return $date->between($this->from, $this->to);
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'days' => $this->days(),
        ];
    }

    public static function fromPeriod(StatsPeriod $period): self
    {
        $now = CarbonImmutable::now();

        return match ($period) {
            StatsPeriod::TODAY => new self($now->startOfDay(), $now->endOfDay()),
            StatsPeriod::YESTERDAY => new self(
                $now->subDay()->startOfDay(),
                $now->subDay()->endOfDay()
            ),
            StatsPeriod::LAST_7_DAYS => new self(
                $now->subDays(6)->startOfDay(),
                $now->endOfDay()
            ),
            StatsPeriod::LAST_30_DAYS => new self(
                $now->subDays(29)->startOfDay(),
                $now->endOfDay()
            ),
            StatsPeriod::THIS_WEEK => new self(
                $now->startOfWeek(),
                $now->endOfWeek()
            ),
            StatsPeriod::LAST_WEEK => new self(
                $now->subWeek()->startOfWeek(),
                $now->subWeek()->endOfWeek()
            ),
            StatsPeriod::THIS_MONTH => new self(
                $now->startOfMonth(),
                $now->endOfMonth()
            ),
            StatsPeriod::LAST_MONTH => new self(
                $now->subMonth()->startOfMonth(),
                $now->subMonth()->endOfMonth()
            ),
            StatsPeriod::THIS_YEAR => new self(
                $now->startOfYear(),
                $now->endOfYear()
            ),
            StatsPeriod::CUSTOM => throw new \InvalidArgumentException('CUSTOM requires from/to'),
        };
    }

    public static function custom(string $from, string $to): self
    {
        return new self(
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay()
        );
    }
}
