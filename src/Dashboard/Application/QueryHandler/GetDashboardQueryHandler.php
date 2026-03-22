<?php

declare(strict_types=1);

namespace App\Dashboard\Application\QueryHandler;

use App\Dashboard\Application\Query\GetDashboardQuery;
use App\Dashboard\Application\Query\ReadModel\DashboardSummaryReadModel;
use App\Dashboard\Application\Query\ReadModel\PaginatedReadModel;
use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use App\Dashboard\Infrastructure\Repository\DashboardReadRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsMessageHandler(bus: 'query.bus')]
final class GetDashboardQueryHandler
{
    private const CACHE_TTL = 60;

    public function __construct(
        private readonly DashboardReadRepository $readRepository,
        private readonly CacheInterface          $cache,
        private readonly LoggerInterface         $logger
    ) {}

    public function __invoke(GetDashboardQuery $query): array
    {
        $pageKey    = DashboardCacheInvalidator::keyFor($query->filterStatus) . '.p' . $query->page;
        $summaryKey = DashboardCacheInvalidator::keyFor($query->filterStatus) . '.summary';

        $paginated = $this->cache->get($pageKey, function (ItemInterface $item) use ($query, $pageKey) {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->debug('Cache miss — fetching page from DB', [
                'cache_key' => $pageKey,
                'page'      => $query->page,
                'page_size' => $query->pageSize,
                'filter'    => $query->filterStatus,
            ]);

            $items = $this->readRepository->fetchPage(
                $query->page,
                $query->pageSize,
                $query->filterStatus
            );
            $total = $this->readRepository->countAll($query->filterStatus);

            return new PaginatedReadModel(
                items: $items,
                totalCount: $total,
                page: $query->page,
                pageSize: $query->pageSize,
            );
        });

        $summary = $this->cache->get($summaryKey, function (ItemInterface $item) use ($summaryKey) {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->debug('Cache miss — fetching summary', ['cache_key' => $summaryKey]);

            $counts = $this->readRepository->fetchStatusCounts();
            $agg    = $this->readRepository->fetchAggregateStats();

            return [
                'total'           => array_sum($counts),
                'active'          => (int) ($counts['active']   ?? 0),
                'inactive'        => (int) ($counts['inactive'] ?? 0),
                'error'           => (int) ($counts['error']    ?? 0),
                'pending'         => (int) ($counts['pending']  ?? 0),
                'slow'            => $agg['slow_count'],
                'never_checked'   => $agg['never_checked'],
                'avg_response_ms' => $agg['avg_response_ms'],
                'health_score'    => $this->healthScore($counts, $agg),
            ];
        });

        return [
            'paginated'  => $paginated,
            'statistics' => $summary,
        ];
    }

    private function healthScore(array $counts, array $agg): float
    {
        $total  = array_sum($counts);
        $active = (int) ($counts['active'] ?? 0);

        if ($total === 0) {
            return 0.0;
        }

        $checked   = $total - $agg['never_checked'];
        $notSlow   = $checked - $agg['slow_count'];
        $healthy   = min($active, max(0, $notSlow));

        return round(($healthy / $total) * 100, 1);
    }
}
