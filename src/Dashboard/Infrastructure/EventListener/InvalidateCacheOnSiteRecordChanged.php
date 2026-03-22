<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\EventListener;

use App\Dashboard\Domain\Event\SiteRecordCreated;
use App\Dashboard\Domain\Event\SiteRecordDeleted;
use App\Dashboard\Domain\Event\SiteRecordUpdated;
use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class InvalidateCacheOnSiteRecordChanged
{
    public function __construct(
        private readonly DashboardCacheInvalidator $cacheInvalidator
    ) {}

    #[AsMessageHandler(bus: 'event.bus')]
    public function onCreated(SiteRecordCreated $event): void
    {
        $this->cacheInvalidator->invalidateAll();
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onUpdated(SiteRecordUpdated $event): void
    {
        $this->cacheInvalidator->invalidateAll();
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onDeleted(SiteRecordDeleted $event): void
    {
        $this->cacheInvalidator->invalidateAll();
    }
}
