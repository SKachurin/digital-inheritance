<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LocalizedPageRedirectController extends AbstractController
{
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
}