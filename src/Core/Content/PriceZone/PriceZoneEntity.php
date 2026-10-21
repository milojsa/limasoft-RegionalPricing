<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Content\PriceZone;

use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PriceZoneEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;

    protected ?string $ruleId = null;

    protected int $priority = 0;

    protected bool $serviceable = true;

    protected bool $active = true;

    protected ?RuleEntity $rule = null;

    protected ?PriceZonePostcodeCollection $postcodes = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRuleId(): ?string
    {
        return $this->ruleId;
    }

    public function setRuleId(?string $ruleId): void
    {
        $this->ruleId = $ruleId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isServiceable(): bool
    {
        return $this->serviceable;
    }

    public function setServiceable(bool $serviceable): void
    {
        $this->serviceable = $serviceable;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getRule(): ?RuleEntity
    {
        return $this->rule;
    }

    public function setRule(?RuleEntity $rule): void
    {
        $this->rule = $rule;
    }

    public function getPostcodes(): ?PriceZonePostcodeCollection
    {
        return $this->postcodes;
    }

    public function setPostcodes(?PriceZonePostcodeCollection $postcodes): void
    {
        $this->postcodes = $postcodes;
    }
}
