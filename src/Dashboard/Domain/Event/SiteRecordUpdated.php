<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Event;

final class SiteRecordUpdated
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly string $siteRecordId,
        public readonly string $name,
        public readonly string $url,
        public readonly string $status
    ) {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
