<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Psr\Log\LoggerInterface;

final class SlowQueryLoggerStatement extends AbstractStatementMiddleware
{
    public function __construct(
        StatementInterface       $wrappedStatement,
        private readonly string          $sql,
        private readonly LoggerInterface $logger,
        private readonly int             $thresholdMs
    ) {
        parent::__construct($wrappedStatement);
    }

    public function execute($params = null): Result
    {
        $start  = microtime(true);
        $result = parent::execute($params);
        $ms     = (int) round((microtime(true) - $start) * 1000);

        if ($ms >= $this->thresholdMs) {
            $this->logger->warning('Slow prepared query detected', [
                'sql'          => mb_substr($this->sql, 0, 500),
                'duration_ms'  => $ms,
                'threshold_ms' => $this->thresholdMs,
            ]);
        }

        return $result;
    }
}
