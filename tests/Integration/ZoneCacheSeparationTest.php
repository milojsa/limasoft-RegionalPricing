<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Test\Integration;

use Doctrine\DBAL\Connection;
use Limasoft\RegionalPricing\Service\PostcodeProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

// this is the test the whole approach stands on. two postcodes in different zones must not
// see each other's zone rule, including when the context cache has already been warmed by
// the first of them.
class ZoneCacheSeparationTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const INNER_POSTCODE = '10115';
    private const OUTER_POSTCODE = '12045';

    private string $innerRuleId;
    private string $outerRuleId;

    protected function setUp(): void
    {
        $zoneRepository = $this->getContainer()->get('limasoft_price_zone.repository');
        static::assertInstanceOf(EntityRepository::class, $zoneRepository);

        $innerId = Uuid::randomHex();
        $outerId = Uuid::randomHex();

        $zoneRepository->create([
            [
                'id' => $innerId,
                'name' => 'Test inner ' . $innerId,
                'priority' => 20,
                'serviceable' => true,
                'active' => true,
                'postcodes' => [
                    ['countryIso' => 'DE', 'pattern' => self::INNER_POSTCODE],
                ],
            ],
            [
                'id' => $outerId,
                'name' => 'Test outer ' . $outerId,
                'priority' => 10,
                'serviceable' => true,
                'active' => true,
                'postcodes' => [
                    ['countryIso' => 'DE', 'pattern' => '120*'],
                ],
            ],
        ], Context::createDefaultContext());

        $this->innerRuleId = $this->ruleIdOf($innerId);
        $this->outerRuleId = $this->ruleIdOf($outerId);
    }

    public function testZonesEachGetTheirOwnRule(): void
    {
        static::assertNotSame($this->innerRuleId, $this->outerRuleId);
    }

    public function testSecondPostcodeDoesNotInheritTheFirstZone(): void
    {
        // warm the context cache with the inner zone first, which is what used to leak
        $inner = $this->createContextForPostcode(self::INNER_POSTCODE);
        $outer = $this->createContextForPostcode(self::OUTER_POSTCODE);

        static::assertContains($this->innerRuleId, $inner->getRuleIds());
        static::assertNotContains($this->outerRuleId, $inner->getRuleIds());

        static::assertContains($this->outerRuleId, $outer->getRuleIds());
        static::assertNotContains($this->innerRuleId, $outer->getRuleIds());
    }

    public function testUnknownPostcodeAddsNoZoneRule(): void
    {
        $context = $this->createContextForPostcode('99999');

        static::assertNotContains($this->innerRuleId, $context->getRuleIds());
        static::assertNotContains($this->outerRuleId, $context->getRuleIds());
    }

    // the cache hash shopware sends to the reverse proxy is built from the rule ids, so
    // different rule ids mean different cached pages
    public function testRuleIdsDifferSoTheCacheHashDiffers(): void
    {
        $inner = $this->createContextForPostcode(self::INNER_POSTCODE);
        $outer = $this->createContextForPostcode(self::OUTER_POSTCODE);

        static::assertNotSame(
            $this->hashOf($inner->getRuleIds()),
            $this->hashOf($outer->getRuleIds())
        );
    }

    private function createContextForPostcode(string $postcode): SalesChannelContext
    {
        $requestStack = $this->getContainer()->get(RequestStack::class);
        static::assertInstanceOf(RequestStack::class, $requestStack);

        // the cookie path on purpose. passing the postcode as a context option would land it
        // in the context cache key and hide exactly the bug this test is here to catch.
        $request = new Request();
        $request->cookies->set(PostcodeProvider::COOKIE_NAME, $postcode);
        $requestStack->push($request);

        try {
            // the id is the core class name, but the instance is the outermost decorator,
            // which is this plugin's factory
            $factory = $this->getContainer()->get(SalesChannelContextFactory::class);
            static::assertInstanceOf(AbstractSalesChannelContextFactory::class, $factory);

            return $factory->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        } finally {
            $requestStack->pop();
        }
    }

    private function ruleIdOf(string $zoneId): string
    {
        $ruleId = $this->getContainer()->get(Connection::class)->fetchOne(
            'SELECT LOWER(HEX(rule_id)) FROM limasoft_price_zone WHERE id = :id',
            ['id' => Uuid::fromHexToBytes($zoneId)]
        );

        static::assertIsString($ruleId);
        static::assertNotSame('', $ruleId);

        return $ruleId;
    }

    private function hashOf(array $ruleIds): string
    {
        sort($ruleIds);

        return md5(implode('-', $ruleIds));
    }
}
