<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Test\Service;

use Limasoft\RegionalPricing\Service\PostcodeNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PostcodeNormalizerTest extends TestCase
{
    #[DataProvider('postcodeProvider')]
    public function testNormalize(?string $input, string $countryIso, ?string $expected): void
    {
        static::assertSame($expected, (new PostcodeNormalizer())->normalize($input, $countryIso));
    }

    public static function postcodeProvider(): array
    {
        return [
            'plain' => ['10115', 'DE', '10115'],
            'spaced' => [' 10 115 ', 'DE', '10115'],
            'country prefix' => ['DE-10115', 'DE', '10115'],
            'too short' => ['1011', 'DE', null],
            'too long' => ['101150', 'DE', null],
            'letters rejected' => ['1011A', 'DE', null],
            'null' => [null, 'DE', null],
            'blank' => ['   ', 'DE', null],
            'austria' => ['1010', 'AT', '1010'],
            'austria wrong length' => ['10115', 'AT', null],
            'unknown country passes through' => ['SW1A1AA', 'GB', 'SW1A1AA'],
        ];
    }
}
