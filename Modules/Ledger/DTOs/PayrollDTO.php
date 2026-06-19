<?php

namespace Modules\Ledger\DTOs;

use App\Interfaces\DTOInterface;

final readonly class PayrollDTO implements DTOInterface
{
    public function __construct(
        public ?string $type = null,
        public ?int $serviceProviderProfileId = null,
        public ?float $baseSalary = null,
        public ?float $perTaskRate = null,
        public ?bool $isActive = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $notes = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            type: $array['type'] ?? null,
            serviceProviderProfileId: isset($array['service_provider_profile_id']) ? (int) $array['service_provider_profile_id'] : null,
            baseSalary: isset($array['base_salary']) ? (float) $array['base_salary'] : null,
            perTaskRate: isset($array['per_task_rate']) ? (float) $array['per_task_rate'] : null,
            isActive: $array['is_active'] ?? null,
            startDate: $array['start_date'] ?? null,
            endDate: $array['end_date'] ?? null,
            notes: $array['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'service_provider_profile_id' => $this->serviceProviderProfileId,
            'base_salary' => $this->baseSalary,
            'per_task_rate' => $this->perTaskRate,
            'is_active' => $this->isActive,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'notes' => $this->notes,
        ], fn ($value) => $value !== null);
    }
}
