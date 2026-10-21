<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

class ResolvedZone
{
    public function __construct(
        public readonly string $zoneId,
        public readonly string $name,
        public readonly ?string $ruleId,
        public readonly bool $serviceable,
    ) {
    }

    public function ruleIds(): array
    {
        return $this->ruleId === null ? [] : [$this->ruleId];
    }
}
