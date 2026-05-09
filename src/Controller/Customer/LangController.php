<?php

namespace App\Controller\Customer;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class LangController extends AbstractController
{
    private const LANGUAGE_COOKIE = 'preferred_language';
    private const COOKIE_DURATION = '+1 year';
    private const AVAILABLE_LANGUAGES = ['en', 'ru', 'es'];

    private const STATIC_PAGES = [
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
        private TranslatorInterface $translator,
    ) {
    }

    public function changeLanguage(string $lang): Response
    {
        if (!in_array($lang, self::AVAILABLE_LANGUAGES, true)) {
            $lang = 'en';
        }

        $request = $this->requestStack->getCurrentRequest();
        $route = $request?->attributes->get('_route');

        $redirectUrl = $this->generateUrl('localized_home', [
            '_locale' => $lang,
        ]);

        if ($request) {
            if ($route === 'localized_home' || $route === 'home') {
                $redirectUrl = $this->generateUrl('localized_home', [
                    '_locale' => $lang,
                ]);
            }

            if ($route === 'localized_static_page') {
                $currentLocale = $request->attributes->get('_locale', 'en');
                $currentSlug = $request->attributes->get('slug');

                $pageKey = $this->resolvePageKeyFromSlug($currentSlug, $currentLocale);

                if ($pageKey !== null) {
                    $redirectUrl = $this->generateUrl('localized_static_page', [
                        '_locale' => $lang,
                        'slug' => $this->translator->trans(
                            'slug.' . $pageKey,
                            [],
                            'routes',
                            $lang
                        ),
                    ]);
                }
            }
        }

        $response = $this->redirect($redirectUrl);

        $cookie = Cookie::create(self::LANGUAGE_COOKIE)
            ->withValue($lang)
            ->withExpires(new \DateTime(self::COOKIE_DURATION))
            ->withPath('/')
            ->withSecure(true)
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

    private function resolvePageKeyFromSlug(?string $slug, string $locale): ?string
    {
        if (!$slug) {
            return null;
        }

        foreach (self::STATIC_PAGES as $pageKey) {
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