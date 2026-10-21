<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Storefront\Framework\Cookie;

use Limasoft\RegionalPricing\Service\PostcodeProvider;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

// the postcode cookie is technically required, it carries no tracking, but it still has to
// appear in the consent list for the shop to be honest about what it stores
class ZoneCookieProvider implements CookieProviderInterface
{
    private const REQUIRED_GROUP = 'cookie.groupRequired';

    public function __construct(private readonly CookieProviderInterface $decorated)
    {
    }

    public function getCookieGroups(): array
    {
        $groups = $this->decorated->getCookieGroups();

        foreach ($groups as $index => $group) {
            if (($group['snippet_name'] ?? null) !== self::REQUIRED_GROUP) {
                continue;
            }

            $groups[$index]['entries'][] = [
                'snippet_name' => 'limasoft.regionalPricing.cookie.postcode',
                'cookie' => PostcodeProvider::COOKIE_NAME,
                'value' => '',
                'expiration' => '30',
            ];

            break;
        }

        return $groups;
    }
}
