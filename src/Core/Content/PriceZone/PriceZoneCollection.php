<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Content\PriceZone;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class PriceZoneCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PriceZoneEntity::class;
    }
}
