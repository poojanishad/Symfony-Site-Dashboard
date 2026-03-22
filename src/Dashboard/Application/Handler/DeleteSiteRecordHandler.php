<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Handler;

use App\Dashboard\Application\Command\DeleteSiteRecordCommand;
use App\Dashboard\Domain\Repository\SiteRecordRepositoryInterface;
use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use App\Shared\Infrastructure\EventListener\DomainEventAuditLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class DeleteSiteRecordHandler
{
    public function __construct(
        private readonly SiteRecordRepositoryInterface $repository,
        private readonly MessageBusInterface           $eventBus,
        private readonly DomainEventAuditLogger        $auditLogger,
        private readonly DashboardCacheInvalidator     $cacheInvalidator
    ) {}

    public function __invoke(DeleteSiteRecordCommand $command): void
    {
        $siteRecord = $this->repository->findById($command->id);

        if ($siteRecord === null) {
            throw new \DomainException(sprintf('SiteRecord "%s" not found.', $command->id));
        }

        $siteRecord->markDeleted();

        foreach ($siteRecord->pullDomainEvents() as $event) {
            $this->auditLogger->log($event);
            $this->eventBus->dispatch($event);
        }

        $this->repository->delete($siteRecord);
        $this->cacheInvalidator->invalidateAll();
    }
}
