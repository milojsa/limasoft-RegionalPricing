<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class PostcodeProvider
{
    public const COOKIE_NAME = 'limasoft-postcode';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getPostcode(array $contextOptions = []): ?string
    {
        // an explicit option wins, so the context can be built for a known postcode in tests and jobs
        if (isset($contextOptions[self::COOKIE_NAME]) && is_string($contextOptions[self::COOKIE_NAME])) {
            return $contextOptions[self::COOKIE_NAME];
        }

        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return null;
        }

        $postcode = $request->cookies->get(self::COOKIE_NAME);

        return is_string($postcode) && $postcode !== '' ? $postcode : null;
    }
}
