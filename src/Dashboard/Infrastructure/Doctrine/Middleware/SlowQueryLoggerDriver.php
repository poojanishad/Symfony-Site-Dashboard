<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Psr\Log\LoggerInterface;

final class SlowQueryLoggerDriver extends AbstractDriverMiddleware
{
    public function __construct(
        DriverInterface          $wrappedDriver,
        private readonly LoggerInterface $logger,
        private readonly int             $thresholdMs
    ) {
        parent::__construct($wrappedDriver);
    }

    public function connect(
        #[\SensitiveParameter] array $params
    ): \Doctrine\DBAL\Driver\Connection {
        return new SlowQueryLoggerConnection(
            parent::connect($params),
            $this->logger,
            $this->thresholdMs
        );
    }
}
