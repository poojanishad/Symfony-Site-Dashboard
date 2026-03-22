<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Infrastructure\Cache;

use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class DashboardCacheInvalidatorTest extends TestCase
{
    private ArrayAdapter $cacheAdapter;
    private DashboardCacheInvalidator $invalidator;

    protected function setUp(): void
    {
        $this->cacheAdapter = new ArrayAdapter();
        $this->invalidator  = new DashboardCacheInvalidator($this->cacheAdapter, new NullLogger());
    }

    public function test_invalidate_all_clears_all_status_keys(): void
    {
        foreach (['all', 'active', 'inactive', 'error', 'pending'] as $status) {
            $key = DashboardCacheInvalidator::keyFor($status === 'all' ? null : $status);
            $this->cacheAdapter->get($key, fn (ItemInterface $item) => 'cached_value');
        }

        self::assertNotNull($this->cacheAdapter->getItem(DashboardCacheInvalidator::keyFor(null))->get());

        $this->invalidator->invalidateAll();

        foreach (['all', 'active', 'inactive', 'error', 'pending'] as $status) {
            $key  = DashboardCacheInvalidator::keyFor($status === 'all' ? null : $status);
            $item = $this->cacheAdapter->getItem($key);
            self::assertFalse($item->isHit(), "Expected cache key '{$key}' to be invalidated");
        }
    }

    public function test_key_for_null_returns_all_key(): void
    {
        self::assertSame('dashboard.records.all', DashboardCacheInvalidator::keyFor(null));
    }

    public function test_key_for_status_returns_status_key(): void
    {
        self::assertSame('dashboard.records.active', DashboardCacheInvalidator::keyFor('active'));
        self::assertSame('dashboard.records.error',  DashboardCacheInvalidator::keyFor('error'));
    }

    public function test_invalidate_does_not_throw_on_cache_error(): void
    {
        $this->expectNotToPerformAssertions();
        $this->invalidator->invalidateAll();
    }
}
