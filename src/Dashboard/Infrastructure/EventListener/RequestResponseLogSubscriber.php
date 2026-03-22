<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Stopwatch\Stopwatch;

final class RequestResponseLogSubscriber implements EventSubscriberInterface
{
    /** @var array<string, float> */
    private array $startTimes = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Stopwatch       $stopwatch
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onRequest',  20],
            KernelEvents::RESPONSE => ['onResponse', -10],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $key     = $this->requestKey($request);

        $this->startTimes[$key] = microtime(true);
        $this->stopwatch->start($key, 'http');

        $this->logger->info('HTTP request received', [
            'method'     => $request->getMethod(),
            'path'       => $request->getPathInfo(),
            'query'      => $request->getQueryString(),
            'ip'         => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request  = $event->getRequest();
        $response = $event->getResponse();
        $key      = $this->requestKey($request);

        $durationMs = isset($this->startTimes[$key])
            ? round((microtime(true) - $this->startTimes[$key]) * 1000, 2)
            : null;

        if ($this->stopwatch->isStarted($key)) {
            $this->stopwatch->stop($key);
        }

        unset($this->startTimes[$key]);

        $statusCode = $response->getStatusCode();
        $level      = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');

        $this->logger->log($level, 'HTTP response sent', [
            'method'      => $request->getMethod(),
            'path'        => $request->getPathInfo(),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
        ]);
    }

    private function requestKey(\Symfony\Component\HttpFoundation\Request $request): string
    {
        return md5($request->getMethod() . $request->getPathInfo() . spl_object_id($request));
    }
}
