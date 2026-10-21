<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Subscriber;

use Limasoft\RegionalPricing\Core\Content\PriceZone\PriceZoneDefinition;
use Limasoft\RegionalPricing\Service\ZoneRuleSynchronizer;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ZoneRuleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $zoneRepository,
        private readonly ZoneRuleSynchronizer $synchronizer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PriceZoneDefinition::ENTITY_NAME . '.written' => 'onZoneWritten',
        ];
    }

    public function onZoneWritten(EntityWrittenEvent $event): void
    {
        $ids = $event->getIds();

        if ($ids === []) {
            return;
        }

        // only zones still without a rule, which also stops this from looping on the update below
        $criteria = new Criteria($ids);
        $criteria->addFilter(new EqualsFilter('ruleId', null));

        $zones = $this->zoneRepository->search($criteria, $event->getContext())->getEntities();

        foreach ($zones as $zone) {
            $this->synchronizer->ensureRule($zone->getId(), $zone->getName(), $event->getContext());
        }
    }
}
