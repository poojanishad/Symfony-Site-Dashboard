<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Handler;

use App\Dashboard\Application\Command\CreateSiteRecordCommand;
use App\Dashboard\Domain\Entity\SiteRecord;
use App\Dashboard\Domain\Repository\SiteRecordRepositoryInterface;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use App\Dashboard\Domain\ValueObject\SiteUrl;
use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use App\Shared\Infrastructure\EventListener\DomainEventAuditLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateSiteRecordHandler
{
    public function __construct(
        private readonly SiteRecordRepositoryInterface $repository,
        private readonly MessageBusInterface           $eventBus,
        private readonly DomainEventAuditLogger        $auditLogger,
        private readonly DashboardCacheInvalidator     $cacheInvalidator
    ) {}

    public function __invoke(CreateSiteRecordCommand $command): string
    {
        $siteRecord = SiteRecord::create(
            id: $this->repository->nextIdentity(),
            name: $command->name,
            url: new SiteUrl($command->url),
            status: new SiteStatus($command->status),
            notes: $command->notes
        );

        $this->repository->save($siteRecord);

        foreach ($siteRecord->pullDomainEvents() as $event) {
            $this->auditLogger->log($event);
            $this->eventBus->dispatch($event);
        }

        $this->cacheInvalidator->invalidateAll();

        return $siteRecord->getId();
    }
}
