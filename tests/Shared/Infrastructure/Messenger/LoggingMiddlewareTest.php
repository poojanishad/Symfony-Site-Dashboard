<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Messenger;

use App\Shared\Infrastructure\Messenger\LoggingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class LoggingMiddlewareTest extends TestCase
{
    public function test_logs_info_on_success(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))
               ->method('info');

        $middleware = new LoggingMiddleware($logger);
        $message    = new \stdClass();
        $envelope   = new Envelope($message);

        $stack = $this->createMock(StackInterface::class);
        $next  = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willReturn($envelope);
        $stack->method('next')->willReturn($next);

        $middleware->handle($envelope, $stack);
    }

    public function test_logs_error_and_rethrows_on_failure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info');
        $logger->expects(self::once())->method('error');

        $middleware = new LoggingMiddleware($logger);
        $envelope   = new Envelope(new \stdClass());

        $stack = $this->createMock(StackInterface::class);
        $next  = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willThrowException(new \RuntimeException('boom'));
        $stack->method('next')->willReturn($next);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $middleware->handle($envelope, $stack);
    }
}
