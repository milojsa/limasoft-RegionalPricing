<?php declare(strict_types=1);

namespace Limasoft\RegionalPricing\Storefront\Controller;

use Limasoft\RegionalPricing\Service\PostcodeNormalizer;
use Limasoft\RegionalPricing\Service\PostcodeProvider;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PostcodeGateController extends StorefrontController
{
    private const COOKIE_LIFETIME = 60 * 60 * 24 * 30;

    public function __construct(private readonly PostcodeNormalizer $normalizer)
    {
    }

    #[Route(
        path: '/regional-pricing/postcode',
        name: 'frontend.regional_pricing.postcode.save',
        methods: ['POST']
    )]
    public function save(Request $request, SalesChannelContext $context): Response
    {
        $countryIso = $context->getShippingLocation()->getCountry()->getIso() ?? 'DE';
        $postcode = $this->normalizer->normalize($request->request->getString('postcode'), $countryIso);

        if ($postcode === null) {
            $this->addFlash(self::DANGER, $this->trans('limasoft.regionalPricing.gate.invalid'));

            return $this->redirectBack($request);
        }

        $response = $this->redirectBack($request);

        // the cookie only carries the postcode, the price itself comes from the zone rule
        $response->headers->setCookie(
            Cookie::create(PostcodeProvider::COOKIE_NAME, $postcode)
                ->withExpires(time() + self::COOKIE_LIFETIME)
                ->withPath('/')
                ->withHttpOnly(false)
                ->withSecure($request->isSecure())
        );

        return $response;
    }

    #[Route(
        path: '/regional-pricing/postcode/reset',
        name: 'frontend.regional_pricing.postcode.reset',
        methods: ['POST']
    )]
    public function reset(Request $request): Response
    {
        $response = $this->redirectBack($request);
        $response->headers->clearCookie(PostcodeProvider::COOKIE_NAME, '/');

        return $response;
    }

    private function redirectBack(Request $request): Response
    {
        $target = $request->request->getString('redirectTo');

        if ($target === '') {
            $target = 'frontend.home.page';
        }

        return $this->redirectToRoute($target);
    }
}
