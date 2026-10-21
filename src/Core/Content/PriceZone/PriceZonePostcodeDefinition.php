<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\Content\PriceZone;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PriceZonePostcodeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'limasoft_price_zone_postcode';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PriceZonePostcodeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PriceZonePostcodeCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new ApiAware(), new PrimaryKey(), new Required()),
            (new FkField('zone_id', 'zoneId', PriceZoneDefinition::class))->addFlags(new ApiAware(), new Required()),
            (new StringField('country_iso', 'countryIso'))->addFlags(new ApiAware(), new Required()),
            (new StringField('pattern', 'pattern'))->addFlags(new ApiAware(), new Required()),
            new ManyToOneAssociationField('zone', 'zone_id', PriceZoneDefinition::class, 'id', false),
        ]);
    }
}
