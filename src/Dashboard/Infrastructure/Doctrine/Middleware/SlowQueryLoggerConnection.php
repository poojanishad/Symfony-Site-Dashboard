<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Middleware;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

final class SlowQueryLoggerConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        ConnectionInterface      $wrappedConnection,
        private readonly LoggerInterface $logger,
        private readonly int             $thresholdMs
    ) {
        parent::__construct($wrappedConnection);
    }

    public function prepare(string $sql): Statement
    {
        return new SlowQueryLoggerStatement(
            parent::prepare($sql),
            $sql,
            $this->logger,
            $this->thresholdMs
        );
    }

    public function query(string $sql): Result
    {
        $start  = microtime(true);
        $result = parent::query($sql);
        $this->logIfSlow($sql, (int) round((microtime(true) - $start) * 1000));

        return $result;
    }

    public function exec(string $sql): int
    {
        $start  = microtime(true);
        $result = parent::exec($sql);
        $this->logIfSlow($sql, (int) round((microtime(true) - $start) * 1000));

        return $result;
    }

    private function logIfSlow(string $sql, int $ms): void
    {
        if ($ms < $this->thresholdMs) {
            return;
        }

        $this->logger->warning('Slow SQL query detected', [
            'sql'          => mb_substr($sql, 0, 500),
            'duration_ms'  => $ms,
            'threshold_ms' => $this->thresholdMs,
        ]);
    }
}
