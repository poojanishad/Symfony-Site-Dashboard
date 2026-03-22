<?php

declare(strict_types=1);

namespace App\Dashboard\Presentation\Console;

use App\Dashboard\Infrastructure\Repository\DashboardReadRepository;
use App\Dashboard\Infrastructure\Cache\DashboardCacheInvalidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name:        'dashboard:status',
    description: 'Show live dashboard statistics from the read model'
)]
final class DashboardStatusCommand extends Command
{
    public function __construct(
        private readonly DashboardReadRepository   $readRepository,
        private readonly DashboardCacheInvalidator $cacheInvalidator,
        private readonly CacheInterface            $cache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('flush-cache', null, InputOption::VALUE_NONE,    'Flush the read cache before reading')
            ->addOption('slowest',     null, InputOption::VALUE_OPTIONAL, 'Show N slowest sites', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Dashboard Status');

        if ($input->getOption('flush-cache')) {
            $this->cacheInvalidator->invalidateAll();
            $io->comment('Cache flushed.');
        }

        $counts = $this->readRepository->fetchStatusCounts();

        $io->section('Site Record Counts');
        $io->table(
            ['Status', 'Count'],
            array_map(
                fn (string $status, int $count) => [$status, $count],
                array_keys($counts),
                array_values($counts)
            )
        );

        $n       = max(1, (int) $input->getOption('slowest'));
        $slowest = $this->readRepository->fetchSlowest($n);

        if (!empty($slowest)) {
            $io->section("Top {$n} Slowest Sites");
            $rows = array_map(
                fn ($r) => [$r->name, $r->url, $r->responseTimeMs . 'ms', $r->status],
                $slowest
            );
            $io->table(['Name', 'URL', 'Response', 'Status'], $rows);
        } else {
            $io->comment('No sites have been checked yet.');
        }

        $io->section('Cache Keys');
        $statuses = ['all', 'active', 'inactive', 'error', 'pending'];
        $cacheRows = [];
        foreach ($statuses as $s) {
            $key  = DashboardCacheInvalidator::keyFor($s === 'all' ? null : $s);
            $hit  = $this->cache->get($key, fn () => null) !== null ? 'HIT' : 'MISS';
            $cacheRows[] = [$key, $hit];
        }
        $io->table(['Cache Key', 'Status'], $cacheRows);

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
