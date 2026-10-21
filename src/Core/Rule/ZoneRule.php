<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Rule;

use Limasoft\RegionalPricing\Core\Content\PriceZone\PriceZoneDefinition;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Rule\RuleConfig;
use Shopware\Core\Framework\Rule\RuleConstraints;
use Shopware\Core\Framework\Rule\RuleScope;

class ZoneRule extends Rule
{
    public const RULE_NAME = 'limasoftRegionalPriceZone';

    protected ?string $zoneId = null;

    // a zone rule is activated by the resolver injecting its id into the context, never by
    // evaluation. it must return false here, because the root of every rule is wrapped in an
    // AndRule and an AndRule with no children matches everything.
    public function match(RuleScope $scope): bool
    {
        return false;
    }

    public function getConstraints(): array
    {
        return [
            'zoneId' => RuleConstraints::string(),
        ];
    }

    // lets the admin rule builder render this condition without a custom component
    public function getConfig(): RuleConfig
    {
        return (new RuleConfig())->entitySelectField('zoneId', PriceZoneDefinition::ENTITY_NAME);
    }

    public function getZoneId(): ?string
    {
        return $this->zoneId;
    }
}
