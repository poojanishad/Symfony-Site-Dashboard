<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query\ReadModel;

final readonly class DashboardSummaryReadModel
{
    public function __construct(
        public int   $total,
        public int   $active,
        public int   $inactive,
        public int   $error,
        public int   $pending,
        public int   $slow,
        public int   $neverChecked,
        public int   $avgResponseMs,
        public float $healthScore,
    ) {}

    /**
     * @param SiteRecordReadModel[] $records
     */
    public static function fromReadModels(array $records): self
    {
        $total = count($records);

        if ($total === 0) {
            return new self(0, 0, 0, 0, 0, 0, 0, 0, 0.0);
        }

        $active       = 0;
        $inactive     = 0;
        $error        = 0;
        $pending      = 0;
        $slow         = 0;
        $neverChecked = 0;
        $healthy      = 0;
        $totalMs      = 0;
        $checkedCount = 0;

        foreach ($records as $r) {
            match ($r->status) {
                'active'   => $active++,
                'inactive' => $inactive++,
                'error'    => $error++,
                'pending'  => $pending++,
                default    => null,
            };

            if ($r->responseTimeMs === 0) {
                $neverChecked++;
            } else {
                $checkedCount++;
                $totalMs += $r->responseTimeMs;
            }

            if ($r->isSlow(500))   { $slow++; }
            if ($r->isHealthy())   { $healthy++; }
        }

        return new self(
            total:         $total,
            active:        $active,
            inactive:      $inactive,
            error:         $error,
            pending:       $pending,
            slow:          $slow,
            neverChecked:  $neverChecked,
            avgResponseMs: $checkedCount > 0 ? (int) round($totalMs / $checkedCount) : 0,
            healthScore:   round(($healthy / $total) * 100, 1),
        );
    }

    public function toArray(): array
    {
        return [
            'total'           => $this->total,
            'active'          => $this->active,
            'inactive'        => $this->inactive,
            'error'           => $this->error,
            'pending'         => $this->pending,
            'slow'            => $this->slow,
            'never_checked'   => $this->neverChecked,
            'avg_response_ms' => $this->avgResponseMs,
            'health_score'    => $this->healthScore,
        ];
    }
}
