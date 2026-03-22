<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Application\Query\ReadModel;

use App\Dashboard\Application\Query\ReadModel\DashboardSummaryReadModel;
use App\Dashboard\Application\Query\ReadModel\SiteRecordReadModel;
use PHPUnit\Framework\TestCase;

final class SiteRecordReadModelTest extends TestCase
{
    private function row(string $status, int $responseMs = 0): array
    {
        return [
            'id'               => uniqid('', true),
            'name'             => 'Test Site',
            'url'              => 'https://example.com',
            'status'           => $status,
            'response_time_ms' => $responseMs,
            'notes'            => null,
            'created_at'       => '2024-01-01 00:00:00',
            'updated_at'       => '2024-01-01 00:00:00',
        ];
    }

    public function test_from_row_maps_all_fields(): void
    {
        $rm = SiteRecordReadModel::fromRow($this->row('active', 150));

        self::assertSame('active', $rm->status);
        self::assertSame(150, $rm->responseTimeMs);
        self::assertSame('https://example.com', $rm->url);
    }

    public function test_is_healthy_when_active_and_fast(): void
    {
        self::assertTrue(SiteRecordReadModel::fromRow($this->row('active', 200))->isHealthy());
        self::assertFalse(SiteRecordReadModel::fromRow($this->row('active', 600))->isHealthy());
        self::assertFalse(SiteRecordReadModel::fromRow($this->row('error', 100))->isHealthy());
        self::assertFalse(SiteRecordReadModel::fromRow($this->row('active', 0))->isHealthy());
    }

    public function test_is_slow_uses_threshold(): void
    {
        $rm = SiteRecordReadModel::fromRow($this->row('active', 300));

        self::assertTrue($rm->isSlow(200));
        self::assertFalse($rm->isSlow(500));
    }

    public function test_summary_counts_correctly(): void
    {
        $records = [
            SiteRecordReadModel::fromRow($this->row('active',   100)),
            SiteRecordReadModel::fromRow($this->row('active',   200)),
            SiteRecordReadModel::fromRow($this->row('error',    50)),
            SiteRecordReadModel::fromRow($this->row('inactive', 0)),
            SiteRecordReadModel::fromRow($this->row('pending',  0)),
        ];

        $summary = DashboardSummaryReadModel::fromReadModels($records);

        self::assertSame(5, $summary->total);
        self::assertSame(2, $summary->active);
        self::assertSame(1, $summary->error);
        self::assertSame(1, $summary->inactive);
        self::assertSame(1, $summary->pending);
        self::assertSame(2, $summary->neverChecked);
        self::assertSame(116, $summary->avgResponseMs); 
    }

    public function test_summary_health_score(): void
    {
        $records = [
            SiteRecordReadModel::fromRow($this->row('active', 100)),
            SiteRecordReadModel::fromRow($this->row('active', 100)),
            SiteRecordReadModel::fromRow($this->row('error',  0)),  
            SiteRecordReadModel::fromRow($this->row('error',  0)),  
        ];

        $summary = DashboardSummaryReadModel::fromReadModels($records);

        self::assertSame(50.0, $summary->healthScore);
    }

    public function test_summary_returns_zeros_for_empty(): void
    {
        $summary = DashboardSummaryReadModel::fromReadModels([]);

        self::assertSame(0, $summary->total);
        self::assertSame(0.0, $summary->healthScore);
    }

    public function test_to_array_has_expected_keys(): void
    {
        $summary = DashboardSummaryReadModel::fromReadModels([]);
        $arr     = $summary->toArray();

        self::assertArrayHasKey('total',           $arr);
        self::assertArrayHasKey('avg_response_ms', $arr);
        self::assertArrayHasKey('health_score',    $arr);
        self::assertArrayHasKey('never_checked',   $arr);
    }
}
