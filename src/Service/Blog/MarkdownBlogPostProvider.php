<?php

declare(strict_types=1);

namespace App\Service\Blog;

use App\Dto\BlogPostDto;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

final readonly class MarkdownBlogPostProvider
{
    private const SUPPORTED_LOCALES = ['en', 'es', 'ru'];

    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    /**
     * @return BlogPostDto[]
     */
    public function getPublishedPosts(string $locale): array
    {
        $locale = $this->normalizeLocale($locale);
        $directory = $this->getBlogLocaleDirectory($locale);

        if (!is_dir($directory)) {
            return [];
        }

        $posts = [];

        foreach (glob($directory . '/*.md') ?: [] as $filePath) {
            $post = $this->parseFile($filePath, $locale);

            if ($post !== null && $post->published) {
                $posts[] = $post;
            }
        }

        usort($posts, static function (BlogPostDto $a, BlogPostDto $b): int {
            return strtotime($b->date) <=> strtotime($a->date);
        });

        return $posts;
    }

    public function findPublishedPostBySlug(string $locale, string $slug): ?BlogPostDto
    {
        foreach ($this->getPublishedPosts($locale) as $post) {
            if ($post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    public function getPreviousPost(string $locale, string $currentSlug): ?BlogPostDto
    {
        $posts = $this->getPublishedPosts($locale);

        foreach ($posts as $index => $post) {
            if ($post->slug === $currentSlug) {
                return $posts[$index + 1] ?? null;
            }
        }

        return null;
    }

    public function getNextPost(string $locale, string $currentSlug): ?BlogPostDto
    {
        $posts = $this->getPublishedPosts($locale);

        foreach ($posts as $index => $post) {
            if ($post->slug === $currentSlug) {
                return $posts[$index - 1] ?? null;
            }
        }

        return null;
    }

    /**
     * Returns translated versions of the same article by translation_key.
     *
     * @return array<string, BlogPostDto>
     */
    public function getTranslations(BlogPostDto $currentPost): array
    {
        $translations = [];

        foreach (self::SUPPORTED_LOCALES as $locale) {
            foreach ($this->getPublishedPosts($locale) as $post) {
                if ($post->translationKey === $currentPost->translationKey) {
                    $translations[$locale] = $post;
                    break;
                }
            }
        }

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    public function getBlogIndexLocales(): array
    {
        return [
            'en' => 'en',
            'es' => 'es',
            'ru' => 'ru',
        ];
    }

    private function parseFile(string $filePath, string $locale): ?BlogPostDto
    {
        $raw = file_get_contents($filePath);

        if ($raw === false || trim($raw) === '') {
            return null;
        }

        [$metadata, $body] = $this->splitFrontMatter($raw);

        if ($metadata === []) {
            return null;
        }

        $title = (string) ($metadata['title'] ?? '');
        $slug = (string) ($metadata['slug'] ?? '');

        if ($title === '' || $slug === '') {
            return null;
        }

        return new BlogPostDto(
            locale: $locale,
            sourceFile: basename($filePath),
            title: $title,
            slug: $slug,
            date: (string) ($metadata['date'] ?? date('Y-m-d')),
            updated: (string) ($metadata['updated'] ?? $metadata['date'] ?? date('Y-m-d')),
            published: (bool) ($metadata['published'] ?? false),
            description: (string) ($metadata['description'] ?? ''),
            preview: (string) ($metadata['preview'] ?? ''),
            image: (string) ($metadata['image'] ?? ''),
            imageAlt: (string) ($metadata['image_alt'] ?? $title),
            translationKey: (string) ($metadata['translation_key'] ?? $slug),
            category: (string) ($metadata['category'] ?? ''),
            topic: (string) ($metadata['topic'] ?? ''),
            ogLocale: (string) ($metadata['og_locale'] ?? $this->getDefaultOgLocale($locale)),
            contentMarkdown: trim($body),
            contentHtml: $this->renderMarkdown($body),
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function splitFrontMatter(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        if (!str_starts_with($raw, "---\n")) {
            return [[], $raw];
        }

        $endPosition = strpos($raw, "\n---\n", 4);

        if ($endPosition === false) {
            return [[], $raw];
        }

        $yaml = substr($raw, 4, $endPosition - 4);
        $body = substr($raw, $endPosition + 5);

        $metadata = Yaml::parse($yaml);

        if (!is_array($metadata)) {
            return [[], $body];
        }

        return [$metadata, $body];
    }

    private function renderMarkdown(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
        $blocks = preg_split("/\n{2,}/", $markdown) ?: [];

        $html = [];

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if (str_starts_with($block, '## ')) {
                $html[] = '<h2>' . $this->inlineMarkdown(substr($block, 3)) . '</h2>';
                continue;
            }

            if (str_starts_with($block, '### ')) {
                $html[] = '<h3>' . $this->inlineMarkdown(substr($block, 4)) . '</h3>';
                continue;
            }

            if (str_starts_with($block, '> ')) {
                $quote = preg_replace('/^> /m', '', $block) ?? $block;
                $html[] = '<blockquote>' . $this->inlineMarkdown($quote) . '</blockquote>';
                continue;
            }

            if (str_starts_with($block, '- ')) {
                $items = preg_split('/\n/', $block) ?: [];
                $listItems = [];

                foreach ($items as $item) {
                    $item = preg_replace('/^- /', '', trim($item)) ?? $item;
                    $listItems[] = '<li>' . $this->inlineMarkdown($item) . '</li>';
                }

                $html[] = '<ul>' . implode('', $listItems) . '</ul>';
                continue;
            }

            $html[] = '<p>' . $this->inlineMarkdown($block) . '</p>';
        }

        return implode("\n", $html);
    }

    private function inlineMarkdown(string $text): string
    {
        $escaped = htmlspecialchars(trim($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $escaped = preg_replace(
            '/\*\*(.*?)\*\*/',
            '<strong>$1</strong>',
            $escaped
        ) ?? $escaped;

        $escaped = preg_replace_callback(
            '/\[([^\]]+)]\((https?:\/\/[^)\s]+)\)/',
            static function (array $matches): string {
                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                    $matches[2],
                    $matches[1]
                );
            },
            $escaped
        ) ?? $escaped;

        return nl2br($escaped);
    }

    private function getBlogLocaleDirectory(string $locale): string
    {
        return $this->kernel->getProjectDir() . '/content/blog/' . $locale;
    }

    private function normalizeLocale(string $locale): string
    {
        return in_array($locale, self::SUPPORTED_LOCALES, true) ? $locale : 'en';
    }

    private function getDefaultOgLocale(string $locale): string
    {
        return match ($locale) {
            'es' => 'es_ES',
            'ru' => 'ru_RU',
            default => 'en_US',
        };
    }
}