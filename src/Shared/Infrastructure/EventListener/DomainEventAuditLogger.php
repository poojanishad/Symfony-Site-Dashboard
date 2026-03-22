<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use Psr\Log\LoggerInterface;

final class DomainEventAuditLogger
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function log(object $event): void
    {
        $ref  = new \ReflectionClass($event);
        $data = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $value = $prop->getValue($event);
            $data[$prop->getName()] = $value instanceof \DateTimeImmutable
                ? $value->format(\DateTimeInterface::ATOM)
                : $value;
        }

        $this->logger->info('Domain event raised', [
            'event'     => $ref->getShortName(),
            'fqcn'      => $event::class,
            'data'      => $data,
            'raised_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
