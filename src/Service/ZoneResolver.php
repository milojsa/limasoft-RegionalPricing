<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

use Doctrine\DBAL\Connection;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ZoneResolver
{
    public const CACHE_KEY = 'limasoft-price-zone-map';
    public const CACHE_TAG = 'limasoft-price-zone';

    public function __construct(
        private readonly Connection $connection,
        private readonly PostcodeMatcher $matcher,
        private readonly PostcodeNormalizer $normalizer,
        private readonly CacheInterface $cache,
    ) {
    }

    public function resolve(?string $postcode, string $countryIso = 'DE'): ?ResolvedZone
    {
        $countryIso = strtoupper($countryIso);
        $normalized = $this->normalizer->normalize($postcode, $countryIso);

        if ($normalized === null) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($this->zoneMap() as $zone) {
            foreach ($zone['patterns'] as $entry) {
                if ($entry['countryIso'] !== $countryIso) {
                    continue;
                }

                $score = $this->matcher->score($entry['pattern'], $normalized);

                if ($score === PostcodeMatcher::SCORE_NONE) {
                    continue;
                }

                // zone priority decides first, pattern precision only breaks a tie
                if ($best !== null && !$this->beats($zone['priority'], $score, $best['priority'], $bestScore)) {
                    continue;
                }

                $best = $zone;
                $bestScore = $score;
            }
        }

        if ($best === null) {
            return null;
        }

        return new ResolvedZone($best['id'], $best['name'], $best['ruleId'], $best['serviceable']);
    }

    // the rule ids injected into the context, which is what the cache hash is built from
    public function ruleIdsFor(?string $postcode, string $countryIso = 'DE'): array
    {
        return $this->resolve($postcode, $countryIso)?->ruleIds() ?? [];
    }

    private function beats(int $priority, int $score, int $bestPriority, int $bestScore): bool
    {
        if ($priority !== $bestPriority) {
            return $priority > $bestPriority;
        }

        return $score > $bestScore;
    }

    // the whole map is small by design, so it is loaded once and cached instead of queried per request
    private function zoneMap(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->tag([self::CACHE_TAG]);

            return $this->loadZoneMap();
        });
    }

    private function loadZoneMap(): array
    {
        $rows = $this->connection->fetchAllAssociative('
            SELECT LOWER(HEX(zone.`id`)) AS zone_id,
                   zone.`name` AS zone_name,
                   LOWER(HEX(zone.`rule_id`)) AS rule_id,
                   zone.`priority` AS priority,
                   zone.`serviceable` AS serviceable,
                   postcode.`country_iso` AS country_iso,
                   postcode.`pattern` AS pattern
            FROM `limasoft_price_zone` AS zone
            INNER JOIN `limasoft_price_zone_postcode` AS postcode ON postcode.`zone_id` = zone.`id`
            WHERE zone.`active` = 1
            ORDER BY zone.`priority` DESC, zone.`name` ASC
        ');

        $zones = [];

        foreach ($rows as $row) {
            $id = $row['zone_id'];

            if (!isset($zones[$id])) {
                $zones[$id] = [
                    'id' => $id,
                    'name' => $row['zone_name'],
                    'ruleId' => $row['rule_id'],
                    'priority' => (int) $row['priority'],
                    'serviceable' => (bool) $row['serviceable'],
                    'patterns' => [],
                ];
            }

            $zones[$id]['patterns'][] = [
                'countryIso' => strtoupper((string) $row['country_iso']),
                'pattern' => (string) $row['pattern'],
            ];
        }

        return array_values($zones);
    }
}
