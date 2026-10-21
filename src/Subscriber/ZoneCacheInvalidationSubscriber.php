<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Subscriber;

use Limasoft\RegionalPricing\Core\Content\PriceZone\PriceZoneDefinition;
use Limasoft\RegionalPricing\Core\Content\PriceZone\PriceZonePostcodeDefinition;
use Limasoft\RegionalPricing\Service\ZoneResolver;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ZoneCacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CacheInvalidator $cacheInvalidator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onWritten',
        ];
    }

    public function onWritten(EntityWrittenContainerEvent $event): void
    {
        $touched = $event->getEventByEntityName(PriceZoneDefinition::ENTITY_NAME)
            ?? $event->getEventByEntityName(PriceZonePostcodeDefinition::ENTITY_NAME);

        if ($touched === null) {
            return;
        }

        $this->cacheInvalidator->invalidate([ZoneResolver::CACHE_TAG]);
    }
}
