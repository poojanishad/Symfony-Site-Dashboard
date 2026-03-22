<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Application\Query\ReadModel;

use App\Dashboard\Application\Query\ReadModel\PaginatedReadModel;
use App\Dashboard\Application\Query\ReadModel\SiteRecordReadModel;
use PHPUnit\Framework\TestCase;

final class PaginatedReadModelTest extends TestCase
{
    private function readModel(string $status = 'active'): SiteRecordReadModel
    {
        return SiteRecordReadModel::fromRow([
            'id' => uniqid(), 'name' => 'Test', 'url' => 'https://example.com',
            'status' => $status, 'response_time_ms' => 100, 'notes' => null,
            'created_at' => '2024-01-01 00:00:00', 'updated_at' => '2024-01-01 00:00:00',
        ]);
    }

    public function test_total_pages_calculated_correctly(): void
    {
        $p = new PaginatedReadModel([], 100, 1, 50);
        self::assertSame(2, $p->totalPages());

        $p = new PaginatedReadModel([], 101, 1, 50);
        self::assertSame(3, $p->totalPages());

        $p = new PaginatedReadModel([], 50, 1, 50);
        self::assertSame(1, $p->totalPages());
    }

    public function test_has_previous_and_next_page(): void
    {
        $first = new PaginatedReadModel([], 200, 1, 50);
        self::assertFalse($first->hasPreviousPage());
        self::assertTrue($first->hasNextPage());

        $middle = new PaginatedReadModel([], 200, 2, 50);
        self::assertTrue($middle->hasPreviousPage());
        self::assertTrue($middle->hasNextPage());

        $last = new PaginatedReadModel([], 200, 4, 50);
        self::assertTrue($last->hasPreviousPage());
        self::assertFalse($last->hasNextPage());
    }

    public function test_previous_and_next_page_numbers(): void
    {
        $p = new PaginatedReadModel([], 300, 3, 50);
        self::assertSame(2, $p->previousPage());
        self::assertSame(4, $p->nextPage());
    }

    public function test_previous_page_cannot_go_below_1(): void
    {
        $p = new PaginatedReadModel([], 100, 1, 50);
        self::assertSame(1, $p->previousPage());
    }

    public function test_next_page_cannot_exceed_total_pages(): void
    {
        $p = new PaginatedReadModel([], 100, 2, 50);
        self::assertSame(2, $p->nextPage());
    }

    public function test_page_range_returns_window_of_5(): void
    {
        $p = new PaginatedReadModel([], 500, 5, 10);
        self::assertSame([3, 4, 5, 6, 7], $p->pageRange());
    }

    public function test_page_range_clamped_at_start(): void
    {
        $p = new PaginatedReadModel([], 500, 1, 10);
        self::assertSame([1, 2, 3], $p->pageRange());
    }

    public function test_page_range_clamped_at_end(): void
    {
        $p = new PaginatedReadModel([], 500, 50, 10);
        self::assertSame([48, 49, 50], $p->pageRange());
    }

    public function test_items_are_returned(): void
    {
        $items = [$this->readModel(), $this->readModel()];
        $p     = new PaginatedReadModel($items, 2, 1, 50);
        self::assertCount(2, $p->items);
    }

    public function test_zero_total_returns_zero_pages(): void
    {
        $p = new PaginatedReadModel([], 0, 1, 50);
        self::assertSame(0, $p->totalPages());
        self::assertFalse($p->hasPreviousPage());
        self::assertFalse($p->hasNextPage());
    }
}
