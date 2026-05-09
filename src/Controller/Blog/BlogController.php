<?php

declare(strict_types=1);

namespace App\Controller\Blog;

use App\Service\Blog\MarkdownBlogPostProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BlogController extends AbstractController
{
    private const LANGUAGE_COOKIE = 'preferred_language';
    private const COOKIE_DURATION = '+1 year';
    private const SUPPORTED_LOCALES = ['en', 'es', 'ru'];

    public function __construct(
        private readonly MarkdownBlogPostProvider $blogPostProvider,
    ) {
    }

    public function redirectToLocalizedBlog(Request $request): RedirectResponse
    {
        $locale = $request->cookies->get(self::LANGUAGE_COOKIE, 'en');

        if (!in_array($locale, self::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        $response = $this->redirectToRoute('blog_index', [
            '_locale' => $locale,
        ], 302);

        $this->attachLanguageCookie($response, $locale);

        return $response;
    }

    public function index(string $_locale): Response
    {
        if (!in_array($_locale, self::SUPPORTED_LOCALES, true)) {
            $_locale = 'en';
        }

        $posts = array_map(
            static fn ($post): array => $post->toArray(),
            $this->blogPostProvider->getPublishedPosts($_locale)
        );

        $response = $this->render('blog/index.html.twig', [
            'locale' => $_locale,
            'posts' => $posts,
        ]);

        $this->attachLanguageCookie($response, $_locale);

        return $response;
    }

    public function article(string $_locale, string $slug): Response
    {
        if (!in_array($_locale, self::SUPPORTED_LOCALES, true)) {
            $_locale = 'en';
        }

        $article = $this->blogPostProvider->findPublishedPostBySlug($_locale, $slug);

        if ($article === null) {
            throw new NotFoundHttpException('Blog article not found.');
        }

        $translations = [];

        foreach ($this->blogPostProvider->getTranslations($article) as $locale => $translatedPost) {
            $translations[$locale] = $translatedPost->toArray();
        }

        $previousPost = $this->blogPostProvider->getPreviousPost($_locale, $slug);
        $nextPost = $this->blogPostProvider->getNextPost($_locale, $slug);

        $response = $this->render('blog/article.html.twig', [
            'locale' => $_locale,
            'slug' => $slug,
            'article' => $article->toArray(),
            'translations' => $translations,
            'previousPost' => $previousPost?->toArray(),
            'nextPost' => $nextPost?->toArray(),
        ]);

        $this->attachLanguageCookie($response, $_locale);

        return $response;
    }

    private function attachLanguageCookie(Response $response, string $locale): void
    {
        $cookie = Cookie::create(self::LANGUAGE_COOKIE)
            ->withValue($locale)
            ->withExpires(new \DateTime(self::COOKIE_DURATION))
            ->withPath('/')
            ->withSecure(false)
            ->withHttpOnly(true)
            ->withSameSite('lax');

        $response->headers->setCookie($cookie);
    }
}