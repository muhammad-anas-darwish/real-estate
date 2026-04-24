<?php

namespace Modules\Communication\Services\Fcm;

use App\Interfaces\DTOInterface;

readonly final class FcmResult implements DTOInterface
{
    public function __construct(
        public int $successCount = 0,
        public int $failureCount = 0,
        public array $failedTokens = [],
    ) {
    }

    public function totalSent(): int
    {
        return $this->successCount;
    }

    public function hasFailures(): bool
    {
        return $this->failureCount > 0;
    }

    public static function fromRequest(array $array): self
    {
        return new self(
            successCount: $array['successCount'] ?? 0,
            failureCount: $array['failureCount'] ?? 0,
            failedTokens: $array['failedTokens'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'success_count' => $this->successCount,
            'failure_count' => $this->failureCount,
            'total' => $this->successCount + $this->failureCount,
            'failed_tokens' => $this->failedTokens,
        ];
    }
}