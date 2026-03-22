<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $name = (new \ReflectionClass($envelope->getMessage()))->getShortName();

        $this->logger->info('Message dispatched', [
            'message'    => $name,
            'class'      => $envelope->getMessage()::class,
            'dispatched' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        try {
            $result = $stack->next()->handle($envelope, $stack);

            $this->logger->info('Message handled successfully', [
                'message' => $name,
            ]);

            return $result;

        } catch (\Throwable $e) {
            $this->logger->error('Message handling failed', [
                'message'   => $name,
                'exception' => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
