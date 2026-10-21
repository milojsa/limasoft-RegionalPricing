<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Rule;

use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleConstraints;
use Shopware\Core\Framework\Rule\RuleScope;

class ZoneRule extends Rule
{
    public const RULE_NAME = 'limasoftRegionalPriceZone';

    protected ?string $zoneId = null;

    public function getName(): string
    {
        return self::RULE_NAME;
    }

    // a zone rule is activated by the resolver injecting its id into the context, never by
    // evaluation. it must return false here, otherwise an empty condition set would make the
    // rule always true and every zone price would apply to everyone.
    public function match(RuleScope $scope): bool
    {
        return false;
    }

    public function getConstraints(): array
    {
        return [
            'zoneId' => RuleConstraints::uuid(),
        ];
    }

    public function getZoneId(): ?string
    {
        return $this->zoneId;
    }
}
