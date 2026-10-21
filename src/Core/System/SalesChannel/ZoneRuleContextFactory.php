<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Core\System\SalesChannel;

use Limasoft\RegionalPricing\Service\PostcodeProvider;
use Limasoft\RegionalPricing\Service\ZoneResolver;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

// the zone is resolved once here and its rule id is pushed into the context.
// shopware builds the http cache hash from the active rule ids, so two postcodes in
// different zones get different hashes and cannot serve each other a cached price.
//
// this has to be the outermost decorator. CachedSalesChannelContextFactory caches the
// context per sales channel and its key does not include the postcode, so injecting
// inside that cache would store one visitor's zone and hand it to everybody else.
// the decoration priority in services.xml is what keeps the order right.
class ZoneRuleContextFactory extends AbstractSalesChannelContextFactory
{
    public function __construct(
        private readonly AbstractSalesChannelContextFactory $decorated,
        private readonly ZoneResolver $zoneResolver,
        private readonly PostcodeProvider $postcodeProvider,
    ) {
    }

    public function getDecorated(): AbstractSalesChannelContextFactory
    {
        return $this->decorated;
    }

    public function create(string $token, string $salesChannelId, array $options = []): SalesChannelContext
    {
        $context = $this->decorated->create($token, $salesChannelId, $options);

        $postcode = $this->postcodeProvider->getPostcode($options);

        if ($postcode === null) {
            return $context;
        }

        $ruleIds = $this->zoneResolver->ruleIdsFor($postcode, $this->countryIso($context));

        if ($ruleIds === []) {
            return $context;
        }

        $context->setRuleIds(array_values(array_unique(array_merge($context->getRuleIds(), $ruleIds))));

        return $context;
    }

    private function countryIso(SalesChannelContext $context): string
    {
        return $context->getShippingLocation()->getCountry()->getIso() ?? 'DE';
    }
}
