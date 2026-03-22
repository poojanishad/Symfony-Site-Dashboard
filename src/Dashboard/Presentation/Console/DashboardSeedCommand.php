<?php

declare(strict_types=1);

namespace App\Dashboard\Presentation\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;

#[AsCommand(name: 'dashboard:seed', description: 'Seed site records (supports 100,000+)')]
final class DashboardSeedCommand extends Command
{
    private const DEFAULT_COUNT = 100;
    private const BATCH_SIZE    = 1000;

    private const STATUSES = ['active', 'active', 'active', 'inactive', 'error', 'pending'];
    private const DOMAINS  = [
        'google.com','github.com','stripe.com','aws.amazon.com','cloudflare.com',
        'vercel.app','netlify.app','heroku.com','digitalocean.com','linode.com',
        'fastly.com','akamai.com','example.com','myapp.io','legacy-portal.net',
    ];

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count',    'c', InputOption::VALUE_OPTIONAL, 'Number of records to create', self::DEFAULT_COUNT)
            ->addOption('truncate', null, InputOption::VALUE_NONE,    'Truncate table before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io    = new SymfonyStyle($input, $output);
        $count = max(1, (int) $input->getOption('count'));

        $io->title('Dashboard Seeder');
        $io->comment(sprintf('Target: %s records | Batch size: %d', $this->indianFormat($count), self::BATCH_SIZE));

        if ($input->getOption('truncate')) {
            $this->connection->executeStatement('DELETE FROM site_records');
            $io->comment('Table cleared.');
        }

        $progress = new ProgressBar($output, $count);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %elapsed:6s% elapsed');
        $progress->start();

        $inserted = 0;
        $batch    = [];

        for ($i = 0; $i < $count; $i++) {
            $batch[] = $this->makeRow($i);

            if (count($batch) >= self::BATCH_SIZE) {
                $this->insertBatch($batch);
                $inserted += count($batch);
                $batch     = [];
                $progress->advance(self::BATCH_SIZE);
            }
        }

        if (!empty($batch)) {
            $this->insertBatch($batch);
            $inserted += count($batch);
            $progress->advance(count($batch));
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf(
            'Seeded %s records. Open http://127.0.0.1:8000/dashboard to view.',
            $this->indianFormat($inserted)
        ));

        return Command::SUCCESS;
    }

    private function indianFormat(int $n): string
    {
        if ($n < 1000) {
            return (string) $n;
        }
        $s     = (string) $n;
        $last  = substr($s, -3);
        $rest  = substr($s, 0, -3);
        $parts = [];
        while (strlen($rest) > 2) {
            $parts[] = substr($rest, -2);
            $rest    = substr($rest, 0, -2);
        }
        if ($rest !== '') {
            $parts[] = $rest;
        }
        return implode(',', array_reverse($parts)) . ',' . $last;
    }

    private function makeRow(int $index): array
    {
        $status     = self::STATUSES[$index % count(self::STATUSES)];
        $domain     = self::DOMAINS[$index % count(self::DOMAINS)];
        $responseMs = match ($status) {
            'active'   => rand(20, 900),
            'error'    => 0,
            'inactive' => 0,
            'pending'  => 0,
            default    => 0,
        };

        $uuid    = $this->uuid4();
        $created = date('Y-m-d H:i:s', strtotime("-{$index} seconds"));

        return [
            'id'               => $uuid,
            'name'             => "Site #{$index} ({$domain})",
            'url'              => "https://{$index}.{$domain}",
            'status'           => $status,
            'response_time_ms' => $responseMs,
            'notes'            => $index % 5 === 0 ? "Seeded record #{$index}" : null,
            'created_at'       => $created,
            'updated_at'       => $created,
        ];
    }

    private function insertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $columns      = array_keys($rows[0]);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $allPlaceholders = implode(', ', array_fill(0, count($rows), $placeholders));
        $sql          = sprintf(
            'INSERT INTO site_records (%s) VALUES %s',
            implode(', ', $columns),
            $allPlaceholders
        );

        $params = [];
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $params[] = $value;
            }
        }

        $this->connection->executeStatement($sql, $params);
    }

    private function uuid4(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
