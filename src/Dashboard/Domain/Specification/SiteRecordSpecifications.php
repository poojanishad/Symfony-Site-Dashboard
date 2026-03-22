<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Specification;

use App\Dashboard\Domain\Entity\SiteRecord;

final class SiteRecordSpecifications
{
    public function isHealthy(): \Closure
    {
        return static function (SiteRecord $record): bool {
            return $record->getStatus() === 'active'
                && $record->getResponseTimeMs() <= 500
                && $record->getResponseTimeMs() > 0;
        };
    }

    public function isSlow(int $thresholdMs = 500): \Closure
    {
        return static function (SiteRecord $record) use ($thresholdMs): bool {
            return $record->getResponseTimeMs() > $thresholdMs;
        };
    }

    public function neverChecked(): \Closure
    {
        return static function (SiteRecord $record): bool {
            return $record->getResponseTimeMs() === 0;
        };
    }

    public function hasStatus(string $status): \Closure
    {
        return static function (SiteRecord $record) use ($status): bool {
            return $record->getStatus() === $status;
        };
    }

    public function urlContains(string $substring): \Closure
    {
        return static function (SiteRecord $record) use ($substring): bool {
            return str_contains(
                strtolower($record->getUrl()),
                strtolower($substring)
            );
        };
    }

    /**
     * Apply a specification to filter an array of SiteRecords.
     *
     * @param SiteRecord[] $records
     * @return SiteRecord[]
     */
    public function filter(array $records, \Closure $specification): array
    {
        return array_values(array_filter($records, $specification));
    }

    /**
     * Check whether at least one record satisfies the specification.
     *
     * @param SiteRecord[] $records
     */
    public function any(array $records, \Closure $specification): bool
    {
        foreach ($records as $record) {
            if ($specification($record)) {
                return true;
            }
        }

        return false;
    }
}
