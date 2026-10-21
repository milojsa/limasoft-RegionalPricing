<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

use Limasoft\RegionalPricing\Core\Rule\ZoneRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

// every zone needs a rule to hang prices and surcharges on. the client's team creates a zone
// in the admin and the matching rule appears, so no developer is needed for a new zone.
class ZoneRuleSynchronizer
{
    public function __construct(
        private readonly EntityRepository $zoneRepository,
        private readonly EntityRepository $ruleRepository,
    ) {
    }

    public function ensureRule(string $zoneId, string $zoneName, Context $context): string
    {
        $ruleId = Uuid::randomHex();

        $this->ruleRepository->create([[
            'id' => $ruleId,
            'name' => $zoneName,
            'priority' => 1,
            'description' => 'Regional price zone. Activated by postcode, not by conditions.',
            'conditions' => [[
                'type' => ZoneRule::RULE_NAME,
                'value' => ['zoneId' => $zoneId],
            ]],
        ]], $context);

        $this->zoneRepository->update([[
            'id' => $zoneId,
            'ruleId' => $ruleId,
        ]], $context);

        return $ruleId;
    }
}
