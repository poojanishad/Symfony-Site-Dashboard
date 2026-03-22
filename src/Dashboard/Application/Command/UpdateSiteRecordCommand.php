<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Command;

final class UpdateSiteRecordCommand
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $url,
        public readonly string  $status,
        public readonly ?string $notes = null
    ) {}
}
