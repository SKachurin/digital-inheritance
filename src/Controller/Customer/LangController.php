<?php

namespace App\Controller\Customer;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LangController extends AbstractController
{
    private const LANGUAGE_COOKIE = 'preferred_language';
    private const COOKIE_DURATION = '+1 year';

    private const AVAILABLE_LANGUAGES = ['en', 'ru', 'es'];

    private const STATIC_PAGE_KEYS = [
        'about',
        'contact_us',
        'onboarding',
        'terms',
        'privacy',
        'refund',
        'wait',
    ];

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router,
        private TranslatorInterface $translator,
    ) {
    }

    public function changeLanguage(string $lang): Response
    {
        if (!in_array($lang, self::AVAILABLE_LANGUAGES, true)) {
            $lang = 'en';
        }

        $response = $this->createRedirectResponse($lang);

        $cookie = Cookie::create(self::LANGUAGE_COOKIE)
            ->withValue($lang)
            ->withExpires(new \DateTime(self::COOKIE_DURATION))
            ->withPath('/')
            ->withSecure(false) // keep false for localhost; change to true on HTTPS production if needed
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);

        $customer = $this->getUser();
        if ($customer instanceof Customer) {
            $customer->setLang($lang);
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
        }

        return $response;
    }

    private function createRedirectResponse(string $targetLang): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return $this->redirectToRoute('home');
        }

        $referer = $request->headers->get('referer');

        if (!$referer) {
            return $this->redirectToRoute('home');
        }

        $refererPath = parse_url($referer, PHP_URL_PATH);

        if (!is_string($refererPath) || $refererPath === '') {
            return $this->redirectToRoute('home');
        }

        try {
            $matchedRoute = $this->router->match($refererPath);
        } catch (\Throwable) {
            return $this->redirect($referer);
        }

        $routeName = $matchedRoute['_route'] ?? null;

        if ($routeName !== 'localized_static_page') {
            return $this->redirect($referer);
        }

        $currentLocale = $matchedRoute['_locale'] ?? null;
        $currentSlug = $matchedRoute['slug'] ?? null;

        if (!is_string($currentLocale) || !is_string($currentSlug)) {
            return $this->redirect($referer);
        }

        $pageKey = $this->resolvePageKeyBySlug($currentSlug, $currentLocale);

        if ($pageKey === null) {
            return $this->redirect($referer);
        }

        $targetSlug = $this->translator->trans(
            'slug.' . $pageKey,
            [],
            'routes',
            $targetLang
        );

        return $this->redirectToRoute('localized_static_page', [
            '_locale' => $targetLang,
            'slug' => $targetSlug,
        ]);
    }

    private function resolvePageKeyBySlug(string $slug, string $locale): ?string
    {
        foreach (self::STATIC_PAGE_KEYS as $pageKey) {
            $translatedSlug = $this->translator->trans(
                'slug.' . $pageKey,
                [],
                'routes',
                $locale
            );

            if ($slug === $translatedSlug) {
                return $pageKey;
            }
        }

        return null;
    }
}