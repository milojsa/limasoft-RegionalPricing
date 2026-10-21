<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Framework\Cache;

use Limasoft\RegionalPricing\Service\PostcodeProvider;
use Limasoft\RegionalPricing\Service\ZoneResolver;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheKeyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

// injecting the zone rule into the context is not enough on its own. shopware only writes
// the sw-cache-hash cookie, which is what carries the rule ids into the cache key, when a
// customer is logged in or the cart has line items. see CacheResponseSubscriber: the guard
// is `if ($context->getCustomer() || $cart->getLineItems()->count() > 0)`.
//
// an anonymous visitor with an empty cart is exactly the case this plugin exists for, and
// for them the rule ids never reach the reverse proxy. so the zone goes into the cache key
// directly here. a handful of zones means a handful of cache variants per page.
class ZoneCacheKeySubscriber implements EventSubscriberInterface
{
    public const CACHE_KEY_PART = 'limasoft-zone';

    public function __construct(
        private readonly ZoneResolver $zoneResolver,
        private readonly PostcodeProvider $postcodeProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HttpCacheKeyEvent::class => 'onCacheKey',
        ];
    }

    public function onCacheKey(HttpCacheKeyEvent $event): void
    {
        $postcode = $this->postcodeProvider->fromRequest($event->request);
        $zone = $this->zoneResolver->resolve($postcode);

        // visitors with no postcode, or one outside every zone, share a single variant
        $event->add(self::CACHE_KEY_PART, $zone?->zoneId ?? 'none');
    }
}
