<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Test\Service;

use Limasoft\RegionalPricing\Service\PostcodeMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostcodeMatcherTest extends TestCase
{
    private PostcodeMatcher $matcher;

    protected function setUp(): void
    {
        $this->matcher = new PostcodeMatcher();
    }

    #[DataProvider('patternProvider')]
    public function testMatches(string $pattern, string $postcode, bool $expected): void
    {
        static::assertSame($expected, $this->matcher->matches($pattern, $postcode));
    }

    public static function patternProvider(): array
    {
        return [
            'exact hit' => ['10115', '10115', true],
            'exact miss' => ['10115', '10117', false],
            'prefix hit' => ['101*', '10115', true],
            'prefix miss' => ['101*', '10215', false],
            'bare star matches nothing' => ['*', '10115', false],
            'range hit' => ['10000-10999', '10115', true],
            'range lower edge' => ['10000-10999', '10000', true],
            'range upper edge' => ['10000-10999', '10999', true],
            'range miss' => ['10000-10999', '11000', false],
            'range keeps leading zeros' => ['01000-09999', '04103', true],
            'empty pattern' => ['', '10115', false],
            'padded pattern' => [' 10115 ', '10115', true],
        ];
    }

    public function testExactBeatsWildcardAndRange(): void
    {
        $exact = $this->matcher->score('10115', '10115');

        static::assertGreaterThan($this->matcher->score('101*', '10115'), $exact);
        static::assertGreaterThan($this->matcher->score('10000-10999', '10115'), $exact);
    }

    public function testLongerPrefixWins(): void
    {
        static::assertGreaterThan(
            $this->matcher->score('1*', '10115'),
            $this->matcher->score('1011*', '10115')
        );
    }
}
