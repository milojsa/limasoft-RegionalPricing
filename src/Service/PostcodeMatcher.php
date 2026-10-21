<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

class PostcodeMatcher
{
    public const SCORE_NONE = 0;
    public const SCORE_RANGE = 1;
    public const SCORE_PREFIX = 2;
    public const SCORE_EXACT = 3;

    // three pattern forms are supported: 10115, 101*, 10000-10999
    public function score(string $pattern, string $postcode): int
    {
        $pattern = trim($pattern);

        if ($pattern === '' || $postcode === '') {
            return self::SCORE_NONE;
        }

        if (str_contains($pattern, '-')) {
            return $this->inRange($pattern, $postcode) ? self::SCORE_RANGE : self::SCORE_NONE;
        }

        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');

            return $prefix !== '' && str_starts_with($postcode, $prefix)
                ? self::SCORE_PREFIX + strlen($prefix)
                : self::SCORE_NONE;
        }

        return $pattern === $postcode ? self::SCORE_EXACT + strlen($pattern) : self::SCORE_NONE;
    }

    public function matches(string $pattern, string $postcode): bool
    {
        return $this->score($pattern, $postcode) > self::SCORE_NONE;
    }

    private function inRange(string $pattern, string $postcode): bool
    {
        [$from, $to] = array_map('trim', explode('-', $pattern, 2));

        if (!ctype_digit($from) || !ctype_digit($to) || !ctype_digit($postcode)) {
            return false;
        }

        // compare as integers so 09000-10999 behaves as a range, not as strings
        $value = (int) $postcode;

        return $value >= (int) $from && $value <= (int) $to;
    }
}
