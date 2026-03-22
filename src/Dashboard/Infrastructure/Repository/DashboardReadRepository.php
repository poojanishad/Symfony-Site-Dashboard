<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Repository;

use App\Dashboard\Application\Query\ReadModel\SiteRecordReadModel;
use Doctrine\DBAL\Connection;

final class DashboardReadRepository
{
    public function __construct(
        private readonly Connection $connection
    ) {}

    public function fetchPage(
        int     $page,
        int     $pageSize,
        ?string $status = null
    ): array {
        $offset = max(0, ($page - 1) * $pageSize);

        $qb = $this->connection->createQueryBuilder()
            ->select('id', 'name', 'url', 'status', 'response_time_ms', 'notes', 'created_at', 'updated_at')
            ->from('site_records')
            ->orderBy('created_at', 'DESC')
            ->setMaxResults($pageSize)
            ->setFirstResult($offset);

        if ($status !== null) {
            $qb->where('status = :status')->setParameter('status', $status);
        }

        return array_map(
            SiteRecordReadModel::fromRow(...),
            $qb->executeQuery()->fetchAllAssociative()
        );
    }

    public function countAll(?string $status = null): int
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('site_records');

        if ($status !== null) {
            $qb->where('status = :status')->setParameter('status', $status);
        }

        return (int) $qb->executeQuery()->fetchOne();
    }

    public function fetchStatusCounts(): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('status', 'COUNT(*) AS cnt')
            ->from('site_records')
            ->groupBy('status')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_column($rows, 'cnt', 'status');
    }

    public function fetchById(string $id): ?SiteRecordReadModel
    {
        $row = $this->connection->createQueryBuilder()
            ->select('id', 'name', 'url', 'status', 'response_time_ms', 'notes', 'created_at', 'updated_at')
            ->from('site_records')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $row !== false ? SiteRecordReadModel::fromRow($row) : null;
    }

    public function fetchSlowest(int $limit = 10): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'name', 'url', 'status', 'response_time_ms', 'notes', 'created_at', 'updated_at')
            ->from('site_records')
            ->where('response_time_ms > 0')
            ->orderBy('response_time_ms', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(SiteRecordReadModel::fromRow(...), $rows);
    }

    public function fetchAggregateStats(): array
    {
        $row = $this->connection->createQueryBuilder()
            ->select(
                'COALESCE(AVG(CASE WHEN response_time_ms > 0 THEN response_time_ms END), 0) AS avg_ms',
                'SUM(CASE WHEN response_time_ms > 500 THEN 1 ELSE 0 END) AS slow_count',
                'SUM(CASE WHEN response_time_ms = 0 THEN 1 ELSE 0 END) AS never_checked'
            )
            ->from('site_records')
            ->executeQuery()
            ->fetchAssociative();

        return [
            'avg_response_ms' => (int) round((float) ($row['avg_ms'] ?? 0)),
            'slow_count'      => (int) ($row['slow_count'] ?? 0),
            'never_checked'   => (int) ($row['never_checked'] ?? 0),
        ];
    }
}
