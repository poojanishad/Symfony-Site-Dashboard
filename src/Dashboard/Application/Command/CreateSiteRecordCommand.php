<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Command;

final class CreateSiteRecordCommand
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $url,
        public readonly string  $status  = 'pending',
        public readonly ?string $notes   = null
    ) {}
}
