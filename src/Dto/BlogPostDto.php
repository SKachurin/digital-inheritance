<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class BlogPostDto
{
    public function __construct(
        public string $locale,
        public string $sourceFile,
        public string $title,
        public string $slug,
        public string $date,
        public string $updated,
        public bool $published,
        public string $description,
        public string $preview,
        public string $image,
        public string $imageAlt,
        public string $translationKey,
        public string $category,
        public string $topic,
        public string $ogLocale,
        public string $contentMarkdown,
        public string $contentHtml,
    ) {
    }

    public function toArray(): array
    {
        return [
            'locale' => $this->locale,
            'sourceFile' => $this->sourceFile,
            'title' => $this->title,
            'slug' => $this->slug,
            'date' => $this->date,
            'updated' => $this->updated,
            'published' => $this->published,
            'description' => $this->description,
            'preview' => $this->preview,
            'image' => $this->image,
            'image_alt' => $this->imageAlt,
            'translation_key' => $this->translationKey,
            'category' => $this->category,
            'topic' => $this->topic,
            'og_locale' => $this->ogLocale,
            'content_markdown' => $this->contentMarkdown,
            'content_html' => $this->contentHtml,
        ];
    }
}