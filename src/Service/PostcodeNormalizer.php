<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

class PostcodeNormalizer
{
    // digit length per country, anything not listed is accepted as given
    private const LENGTH = [
        'DE' => 5,
        'AT' => 4,
        'CH' => 4,
    ];

    public function normalize(?string $postcode, string $countryIso = 'DE'): ?string
    {
        if ($postcode === null) {
            return null;
        }

        // strip spaces, dots and the country prefix some customers type
        $clean = preg_replace('/[^0-9A-Za-z]/', '', $postcode) ?? '';
        $countryIso = strtoupper($countryIso);
        $clean = strtoupper($clean);

        if (str_starts_with($clean, $countryIso)) {
            $clean = substr($clean, 2);
        }

        if ($clean === '') {
            return null;
        }

        $expected = self::LENGTH[$countryIso] ?? null;
        if ($expected !== null && (strlen($clean) !== $expected || !ctype_digit($clean))) {
            return null;
        }

        return $clean;
    }
}
