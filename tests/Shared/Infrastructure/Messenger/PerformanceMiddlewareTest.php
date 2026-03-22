<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Messenger;

use App\Shared\Infrastructure\Messenger\PerformanceMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class PerformanceMiddlewareTest extends TestCase
{
    public function test_logs_debug_on_fast_handler(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
               ->method('log')
               ->with('debug', self::anything(), self::anything());

        $middleware = new PerformanceMiddleware($logger, new Stopwatch());
        $envelope   = new Envelope(new \stdClass());

        $stack = $this->createMock(StackInterface::class);
        $next  = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willReturn($envelope);
        $stack->method('next')->willReturn($next);

        $middleware->handle($envelope, $stack);
    }

    public function test_still_logs_when_handler_throws(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('log');

        $middleware = new PerformanceMiddleware($logger, new Stopwatch());
        $envelope   = new Envelope(new \stdClass());

        $stack = $this->createMock(StackInterface::class);
        $next  = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willThrowException(new \RuntimeException('fail'));
        $stack->method('next')->willReturn($next);

        $this->expectException(\RuntimeException::class);
        $middleware->handle($envelope, $stack);
    }
}
