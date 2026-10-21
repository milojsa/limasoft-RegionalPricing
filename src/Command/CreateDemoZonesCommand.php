<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Command;

use Limasoft\RegionalPricing\Core\Content\PriceZone\PriceZoneEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// gives a fresh install something to look at. berlin postcodes, one excluded zone.
#[AsCommand(
    name: 'limasoft:regional-pricing:demo-zones',
    description: 'Creates three demo price zones with Berlin postcodes'
)]
class CreateDemoZonesCommand extends Command
{
    private const ZONES = [
        [
            'name' => 'Berlin inner',
            'priority' => 20,
            'serviceable' => true,
            'patterns' => ['10115', '101*', '10200-10299'],
        ],
        [
            'name' => 'Berlin outer',
            'priority' => 10,
            'serviceable' => true,
            'patterns' => ['12*', '13*', '14000-14199'],
        ],
        [
            'name' => 'Outside service area',
            'priority' => 5,
            'serviceable' => false,
            'patterns' => ['20*', '80*'],
        ],
    ];

    public function __construct(private readonly EntityRepository $zoneRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $existing = $this->existingNames($context);
        $payload = [];
        $rows = [];
        $skipped = [];

        foreach (self::ZONES as $zone) {
            // running this twice must not duplicate the demo data
            if (in_array($zone['name'], $existing, true)) {
                $skipped[] = $zone['name'];

                continue;
            }

            $zoneId = Uuid::randomHex();

            $payload[] = [
                'id' => $zoneId,
                'name' => $zone['name'],
                'priority' => $zone['priority'],
                'serviceable' => $zone['serviceable'],
                'active' => true,
                'postcodes' => array_map(static fn (string $pattern): array => [
                    'id' => Uuid::randomHex(),
                    'countryIso' => 'DE',
                    'pattern' => $pattern,
                ], $zone['patterns']),
            ];

            $rows[] = [
                $zone['name'],
                $zone['priority'],
                $zone['serviceable'] ? 'yes' : 'no',
                implode(', ', $zone['patterns']),
            ];
        }

        if ($skipped !== []) {
            $io->note('Already present, left alone: ' . implode(', ', $skipped));
        }

        if ($payload === []) {
            $io->success('Nothing to do, all demo zones exist.');

            return self::SUCCESS;
        }

        $this->zoneRepository->create($payload, $context);

        $io->table(['Zone', 'Priority', 'Serviceable', 'Postcodes'], $rows);
        $io->success('Created ' . \count($payload) . ' zones. Each one now has a rule to attach prices to.');

        return self::SUCCESS;
    }

    private function existingNames(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', array_column(self::ZONES, 'name')));

        return $this->zoneRepository->search($criteria, $context)->getEntities()->map(
            static fn (PriceZoneEntity $zone): string => $zone->getName()
        );
    }
}
