<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Command;

final class DeleteSiteRecordCommand
{
    public function __construct(
        public readonly string $id
    ) {}
}
