<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query\ReadModel;

final readonly class SiteRecordReadModel
{
    public function __construct(
        public string             $id,
        public string             $name,
        public string             $url,
        public string             $status,
        public int                $responseTimeMs,
        public ?string            $notes,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Construct from a raw DBAL associative row (snake_case column names).
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:             (string) $row['id'],
            name:           (string) $row['name'],
            url:            (string) $row['url'],
            status:         (string) $row['status'],
            responseTimeMs: (int)    $row['response_time_ms'],
            notes:          isset($row['notes']) && $row['notes'] !== null
                                ? (string) $row['notes']
                                : null,
            createdAt: new \DateTimeImmutable((string) $row['created_at']),
            updatedAt: new \DateTimeImmutable((string) $row['updated_at']),
        );
    }

    public function isHealthy(): bool
    {
        return $this->status === 'active'
            && $this->responseTimeMs > 0
            && $this->responseTimeMs <= 500;
    }

    public function isSlow(int $thresholdMs = 500): bool
    {
        return $this->responseTimeMs > $thresholdMs;
    }
}
