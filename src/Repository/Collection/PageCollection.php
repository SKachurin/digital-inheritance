<?php

declare(strict_types=1);

namespace App\Repository\Collection;

use Doctrine\Common\Collections\Collection;

readonly class PageCollection
{
    /**
     * @param array<int, string> $items
     * @return void
     */
    public function __construct(
        private int $current,
        private int $pageSize,
        private int $totalPages,
        private int $totalItems,
        private array $items
    ) {}

    public function getPage(): int
    {
        return $this->getCurrent();
    }

    public function getCurrent(): int
    {
        return $this->current;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * @return array<int, string>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
