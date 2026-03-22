<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query\ReadModel;

final readonly class PaginatedReadModel
{
    /**
     * @param SiteRecordReadModel[] $items
     */
    public function __construct(
        public array $items,
        public int   $totalCount,
        public int   $page,
        public int   $pageSize,
    ) {}

    public function totalPages(): int
    {
        return $this->pageSize > 0
            ? (int) ceil($this->totalCount / $this->pageSize)
            : 1;
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages();
    }

    public function previousPage(): int
    {
        return max(1, $this->page - 1);
    }

    public function nextPage(): int
    {
        return min($this->totalPages(), $this->page + 1);
    }

    public function pageRange(): array
    {
        $total = $this->totalPages();
        $start = max(1, $this->page - 2);
        $end   = min($total, $this->page + 2);

        return range($start, $end);
    }
}
