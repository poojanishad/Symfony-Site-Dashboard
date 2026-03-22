<?php

declare(strict_types=1);

namespace App\Dashboard\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ApiLogController extends AbstractController
{
    private const LOG_FILES = [
        'application' => 'application',
        'domain'      => 'domain',
        'dashboard'   => 'dashboard',
        'performance' => 'dashboard_performance',
        'main'        => 'dev',
    ];

    private const PAGE_SIZE = 50;

    public function __construct(
        private readonly string $kernel_logs_dir
    ) {}

    public function index(Request $request): JsonResponse
    {
        $channel = $request->query->get('channel', 'application');
        $level   = $request->query->get('level', '');
        $search  = $request->query->get('search', '');
        $page    = max(1, (int) $request->query->get('page', 1));

        $baseName = self::LOG_FILES[$channel] ?? 'application';
        $logFile  = $this->resolveLogFile($baseName);
        $entries  = $this->parseLogFile($logFile);

        if ($level !== '') {
            $entries = array_filter($entries, fn ($e) => strtolower($e['level']) === strtolower($level));
        }

        if ($search !== '') {
            $entries = array_filter($entries, fn ($e) =>
                str_contains(strtolower($e['message']), strtolower($search)) ||
                str_contains(strtolower($e['raw']), strtolower($search))
            );
        }

        $entries    = array_values($entries);
        $totalCount = count($entries);
        $totalPages = max(1, (int) ceil($totalCount / self::PAGE_SIZE));
        $page       = min($page, $totalPages);
        $paged      = array_slice($entries, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE);

        $allEntries  = $this->parseLogFile($logFile);
        $levelCounts = array_count_values(array_column($allEntries, 'level'));

        return $this->json([
            'data'        => $paged,
            'levelCounts' => $levelCounts,
            'channels'    => array_keys(self::LOG_FILES),
            'meta'        => [
                'currentPage' => $page,
                'totalPages'  => $totalPages,
                'totalCount'  => $totalCount,
            ],
        ]);
    }

    private function resolveLogFile(string $baseName): string
    {
        $plain = $this->kernel_logs_dir . '/' . $baseName . '.log';
        if (file_exists($plain)) {
            return $plain;
        }

        $files = glob($this->kernel_logs_dir . '/' . $baseName . '-*.log') ?: [];
        if (!empty($files)) {
            rsort($files);
            return $files[0];
        }

        return $plain;
    }

    private function levelName(int $level): string
    {
        return match (true) {
            $level >= 600 => 'EMERGENCY',
            $level >= 550 => 'ALERT',
            $level >= 500 => 'CRITICAL',
            $level >= 400 => 'ERROR',
            $level >= 300 => 'WARNING',
            $level >= 250 => 'NOTICE',
            $level >= 200 => 'INFO',
            default       => 'DEBUG',
        };
    }

    private function parseLogFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $lines   = array_filter(explode("\n", file_get_contents($path)));
        $entries = [];

        foreach (array_reverse(array_values($lines)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $json = json_decode($line, true);
            if (is_array($json)) {
                $rawLevel  = $json['level_name'] ?? $json['level'] ?? 'INFO';
                $entries[] = [
                    'level'    => strtoupper(is_int($rawLevel) ? $this->levelName($rawLevel) : (string) $rawLevel),
                    'datetime' => $json['datetime'] ?? '',
                    'message'  => $json['message'] ?? '',
                    'context'  => $json['context'] ?? [],
                    'raw'      => $line,
                ];
                continue;
            }

            if (preg_match('/^\[(.+?)\] \w+\.(\w+): (.+)$/', $line, $m)) {
                $entries[] = [
                    'level'    => strtoupper($m[2]),
                    'datetime' => $m[1],
                    'message'  => $m[3],
                    'context'  => [],
                    'raw'      => $line,
                ];
                continue;
            }

            $entries[] = [
                'level'    => 'INFO',
                'datetime' => '',
                'message'  => $line,
                'context'  => [],
                'raw'      => $line,
            ];
        }

        return $entries;
    }
}
