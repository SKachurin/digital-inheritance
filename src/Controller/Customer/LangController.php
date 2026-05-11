<?php

namespace App\Controller\Customer;

use App\Entity\Customer;
use App\Service\Blog\MarkdownBlogPostProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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
        private RouterInterface $router,
        private MarkdownBlogPostProvider $blogPostProvider,
    ) {
    }

    public function changeLanguage(string $lang): Response
    {
        if (!in_array($lang, self::AVAILABLE_LANGUAGES, true)) {
            $lang = 'en';
        }

        $request = $this->requestStack->getCurrentRequest();

        $redirectUrl = $this->generateUrl('localized_home', [
            '_locale' => $lang,
        ]);

        $referer = $request?->headers->get('referer');

        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH) ?: '/';

            try {
                $matched = $this->router->match($path);
                $route = $matched['_route'] ?? null;

                if ($route === 'home' || $route === 'localized_home') {
                    $redirectUrl = $this->generateUrl('localized_home', [
                        '_locale' => $lang,
                    ]);
                }

                if ($route === 'localized_static_page') {
                    $currentLocale = $matched['_locale'] ?? 'en';
                    $currentSlug = $matched['slug'] ?? null;

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

                if ($route === 'blog_index') {
                    $redirectUrl = $this->generateUrl('blog_index', [
                        '_locale' => $lang,
                    ]);
                }

                if ($route === 'blog_article') {
                    $currentLocale = $matched['_locale'] ?? 'en';
                    $currentSlug = $matched['slug'] ?? null;

                    $currentPost = $this->blogPostProvider->findPublishedPostBySlug(
                        $currentLocale,
                        $currentSlug
                    );

                    if ($currentPost !== null) {
                        $translations = $this->blogPostProvider->getTranslations($currentPost);

                        if (isset($translations[$lang])) {
                            $redirectUrl = $this->generateUrl('blog_article', [
                                '_locale' => $lang,
                                'slug' => $translations[$lang]->slug,
                            ]);
                        } else {
                            $redirectUrl = $this->generateUrl('blog_index', [
                                '_locale' => $lang,
                            ]);
                        }
                    }
                }
            } catch (ResourceNotFoundException) {
                // Keep fallback to localized home.
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