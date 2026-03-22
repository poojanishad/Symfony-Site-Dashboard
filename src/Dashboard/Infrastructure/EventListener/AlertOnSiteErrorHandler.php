<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\EventListener;

use App\Dashboard\Domain\Event\SiteRecordUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class AlertOnSiteErrorHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function __invoke(SiteRecordUpdated $event): void
    {
        if ($event->status !== 'error') {
            return;
        }

        $this->logger->critical('Site transitioned to ERROR — alert triggered', [
            'site_record_id' => $event->siteRecordId,
            'name'           => $event->name,
            'url'            => $event->url,
            'occurred_at'    => $event->occurredAt->format(\DateTimeInterface::ATOM),
        ]);
    }
}
