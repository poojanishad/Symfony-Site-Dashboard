<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Domain\Specification;

use App\Dashboard\Domain\Entity\SiteRecord;
use App\Dashboard\Domain\Specification\SiteRecordSpecifications;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use App\Dashboard\Domain\ValueObject\SiteUrl;
use PHPUnit\Framework\TestCase;

final class SiteRecordSpecificationsTest extends TestCase
{
    private SiteRecordSpecifications $specs;

    protected function setUp(): void
    {
        $this->specs = new SiteRecordSpecifications();
    }

    private function record(string $status, int $responseMs = 0): SiteRecord
    {
        $r = SiteRecord::create(
            id:     uniqid('', true),
            name:   'Test',
            url:    new SiteUrl('https://example.com'),
            status: new SiteStatus($status),
        );
        if ($responseMs > 0) {
            $r->recordResponseTime($responseMs);
        }
        return $r;
    }

    public function test_is_healthy_requires_active_and_fast_response(): void
    {
        $healthy  = $this->record('active', 200);
        $slow     = $this->record('active', 600);
        $inactive = $this->record('inactive', 200);
        $noCheck  = $this->record('active', 0);

        $spec = $this->specs->isHealthy();

        self::assertTrue($spec($healthy));
        self::assertFalse($spec($slow));
        self::assertFalse($spec($inactive));
        self::assertFalse($spec($noCheck));
    }

    public function test_is_slow_uses_custom_threshold(): void
    {
        $r   = $this->record('active', 300);
        $low = $this->specs->isSlow(200);
        $hi  = $this->specs->isSlow(500);

        self::assertTrue($low($r));
        self::assertFalse($hi($r));
    }

    public function test_never_checked(): void
    {
        $unchecked = $this->record('pending', 0);
        $checked   = $this->record('active', 150);

        $spec = $this->specs->neverChecked();

        self::assertTrue($spec($unchecked));
        self::assertFalse($spec($checked));
    }

    public function test_filter_returns_only_matching(): void
    {
        $records = [
            $this->record('active'),
            $this->record('error'),
            $this->record('active'),
            $this->record('pending'),
        ];

        $result = $this->specs->filter($records, $this->specs->hasStatus('active'));

        self::assertCount(2, $result);
    }

    public function test_any_returns_true_when_one_matches(): void
    {
        $records = [
            $this->record('inactive'),
            $this->record('error'),
            $this->record('active', 100),
        ];

        self::assertTrue($this->specs->any($records, $this->specs->isHealthy()));
    }

    public function test_any_returns_false_when_none_match(): void
    {
        $records = [
            $this->record('inactive', 100),
            $this->record('error', 50),
        ];

        self::assertFalse($this->specs->any($records, $this->specs->isHealthy()));
    }
}
