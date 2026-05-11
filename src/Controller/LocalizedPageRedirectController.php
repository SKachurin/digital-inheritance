<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class LocalizedPageRedirectController extends AbstractController
{
    private const DEFAULT_LOCALE = 'en';
    private const ALLOWED_LOCALES = ['en', 'es', 'ru'];
    private const LANGUAGE_COOKIE = 'preferred_language';

    private const PAGES = [
        'about' => [
            'template' => 'about.html.twig',
            'schema_type' => 'AboutPage',
        ],
        'contact_us' => [
            'template' => 'contactUs.html.twig',
            'schema_type' => 'ContactPage',
        ],
        'onboarding' => [
            'template' => 'onboarding.html.twig',
            'schema_type' => 'WebPage',
        ],
        'terms' => [
            'template' => 'legal/terms.html.twig',
            'schema_type' => 'WebPage',
        ],
        'privacy' => [
            'template' => 'legal/privacy.html.twig',
            'schema_type' => 'WebPage',
        ],
        'refund' => [
            'template' => 'legal/refund.html.twig',
            'schema_type' => 'WebPage',
        ],
        'wait' => [
            'template' => 'wait.html.twig',
            'schema_type' => 'WebPage',
        ],
        '404' => [
            'template' => '404.html.twig',
            'schema_type' => 'WebPage',
        ]
    ];

    public function show(
        string $_locale,
        string $slug,
        TranslatorInterface $translator,
    ): Response {
        foreach (self::PAGES as $pageKey => $config) {
            $translatedSlug = $translator->trans(
                'slug.' . $pageKey,
                [],
                'routes',
                $_locale
            );

            if ($slug === $translatedSlug) {
                return $this->render($config['template'], [
                    'page_key' => $pageKey,
                    'schema_type' => $config['schema_type'],
                ]);
            }
        }

        throw $this->createNotFoundException();
    }

    private function redirectToLocalizedStaticPage(
        Request $request,
        TranslatorInterface $translator,
        string $pageKey
    ): RedirectResponse {
        if (!array_key_exists($pageKey, self::PAGES)) {
            throw $this->createNotFoundException();
        }

        $locale = $request->cookies->get(self::LANGUAGE_COOKIE);

        if (!in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = $request->getPreferredLanguage(self::ALLOWED_LOCALES);
        }

        if (!in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = self::DEFAULT_LOCALE;
        }

        $slug = $translator->trans(
            'slug.' . $pageKey,
            [],
            'routes',
            $locale
        );

        return $this->redirectToRoute('localized_static_page', [
            '_locale' => $locale,
            'slug' => $slug,
        ], 301);
    }

    public function redirectOldStaticPage(
        Request $request,
        TranslatorInterface $translator,
        string $pageKey
    ): RedirectResponse {
        return $this->redirectToLocalizedStaticPage($request, $translator, $pageKey);
    }

}