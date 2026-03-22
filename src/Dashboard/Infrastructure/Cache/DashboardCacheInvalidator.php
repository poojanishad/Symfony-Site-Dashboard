<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Cache;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class DashboardCacheInvalidator
{
    private const PREFIX = 'dashboard.records.';
    private const STATUSES = ['all', 'active', 'inactive', 'error', 'pending'];

    public function __construct(
        private readonly CacheInterface  $cache,
        private readonly LoggerInterface $logger
    ) {}

    public function invalidateAll(): void
    {
        foreach (self::STATUSES as $status) {
            $summaryKey = self::keyFor($status === 'all' ? null : $status) . '.summary';
            $this->deleteKey($summaryKey);
        }

        foreach (self::STATUSES as $status) {
            $base = self::keyFor($status === 'all' ? null : $status);
            for ($p = 1; $p <= 20; $p++) {
                $this->deleteKey($base . '.p' . $p);
            }
        }

        $this->logger->debug('Dashboard cache invalidated');
    }

    private function deleteKey(string $key): void
    {
        try {
            $this->cache->delete($key);
        } catch (\Throwable $e) {
            $this->logger->warning('Cache delete failed', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    public static function keyFor(?string $status): string
    {
        return self::PREFIX . ($status ?? 'all');
    }
}
