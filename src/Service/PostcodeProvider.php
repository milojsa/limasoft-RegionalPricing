<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Service;

use Symfony\Component\HttpFoundation\Request;
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

        return $request === null ? null : $this->fromRequest($request);
    }

    // the http cache key is built before the request stack is useful, so that path reads
    // the cookie off the request it is given
    public function fromRequest(Request $request): ?string
    {
        $postcode = $request->cookies->get(self::COOKIE_NAME);

        return is_string($postcode) && $postcode !== '' ? $postcode : null;
    }
}
