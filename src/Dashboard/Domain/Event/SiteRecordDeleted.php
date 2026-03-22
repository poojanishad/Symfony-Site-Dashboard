<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Event;

final class SiteRecordDeleted
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $siteRecordId,
        public readonly string $name
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
