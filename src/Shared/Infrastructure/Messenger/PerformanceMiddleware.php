<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class PerformanceMiddleware implements MiddlewareInterface
{
    private const SLOW_THRESHOLD_MS = 200;

    public function __construct(
        private readonly LoggerInterface $logger, 
        private readonly Stopwatch       $stopwatch
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $name = (new \ReflectionClass($envelope->getMessage()))->getShortName();
        $key  = $name . '_' . uniqid('', true);

        $this->stopwatch->start($key, 'messenger');

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $event      = $this->stopwatch->stop($key);
            $durationMs = $event->getDuration();
            $memoryMb   = round($event->getMemory() / 1024 / 1024, 2);
            $isSlow     = $durationMs > self::SLOW_THRESHOLD_MS;

            $this->logger->log(
                $isSlow ? 'warning' : 'debug',
                $isSlow ? 'Slow handler detected' : 'Handler performance',
                [
                    'message'     => $name,
                    'duration_ms' => $durationMs,
                    'memory_mb'   => $memoryMb,
                    'slow'        => $isSlow,
                    'threshold'   => self::SLOW_THRESHOLD_MS,
                ]
            );
        }
    }
}
