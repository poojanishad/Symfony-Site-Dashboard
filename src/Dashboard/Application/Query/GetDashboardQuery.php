<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

final class GetDashboardQuery
{
    public const PAGE_SIZE = 50;

    public function __construct(
        public readonly ?string $filterStatus = null,
        public readonly int     $page         = 1,
        public readonly int     $pageSize     = self::PAGE_SIZE,
    ) {}

    public function offset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }
}
