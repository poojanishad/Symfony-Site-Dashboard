<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Repository;

use App\Dashboard\Domain\Entity\SiteRecord;

interface SiteRecordRepositoryInterface
{
    public function findById(string $id): ?SiteRecord;

    /** @return SiteRecord[] */
    public function findAll(): array;

    /** @return SiteRecord[] */
    public function findByStatus(string $status): array;

    public function save(SiteRecord $siteRecord): void;

    public function delete(SiteRecord $siteRecord): void;

    public function nextIdentity(): string;
}
