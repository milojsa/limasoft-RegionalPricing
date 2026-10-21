<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Content\PriceZone;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class PriceZonePostcodeEntity extends Entity
{
    use EntityIdTrait;

    protected string $zoneId;

    protected string $countryIso = 'DE';

    protected string $pattern;

    protected ?PriceZoneEntity $zone = null;

    public function getZoneId(): string
    {
        return $this->zoneId;
    }

    public function setZoneId(string $zoneId): void
    {
        $this->zoneId = $zoneId;
    }

    public function getCountryIso(): string
    {
        return $this->countryIso;
    }

    public function setCountryIso(string $countryIso): void
    {
        $this->countryIso = $countryIso;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }

    public function getZone(): ?PriceZoneEntity
    {
        return $this->zone;
    }

    public function setZone(?PriceZoneEntity $zone): void
    {
        $this->zone = $zone;
    }
}
