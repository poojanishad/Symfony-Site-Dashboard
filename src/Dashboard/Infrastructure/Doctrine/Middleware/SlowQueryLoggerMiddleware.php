<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Psr\Log\LoggerInterface;

/**
 * DBAL Middleware: logs every SQL query that exceeds SLOW_THRESHOLD_MS.
 * Registered via services.yaml tag: { name: doctrine.middleware }
 *
 * Writes to the `performance` Monolog channel → dashboard_performance.log
 */
final class SlowQueryLoggerMiddleware implements MiddlewareInterface
{
    private const SLOW_THRESHOLD_MS = 100;

    public function __construct(
        private readonly LoggerInterface $logger  // monolog.logger.performance
    ) {}

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new SlowQueryLoggerDriver($driver, $this->logger, self::SLOW_THRESHOLD_MS);
    }
}
