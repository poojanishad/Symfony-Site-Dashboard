<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\EventListener;

use App\Dashboard\Domain\Event\SiteRecordCreated;
use App\Dashboard\Domain\Event\SiteRecordDeleted;
use App\Dashboard\Domain\Event\SiteRecordUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class MetricsOnDomainEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    #[AsMessageHandler(bus: 'event.bus')]
    public function onCreated(SiteRecordCreated $event): void
    {
        $this->emit($event, ['status' => $event->status]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onUpdated(SiteRecordUpdated $event): void
    {
        $this->emit($event, ['status' => $event->status]);
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onDeleted(SiteRecordDeleted $event): void
    {
        $this->emit($event, []);
    }

    private function emit(object $event, array $labels): void
    {
        $this->logger->info('domain_event_metric', [
            'metric'     => 'site_record_events_total',
            'event_type' => (new \ReflectionClass($event))->getShortName(),
            'labels'     => $labels,
            'value'      => 1,
            'ts'         => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }
}
