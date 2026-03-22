<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Infrastructure\EventListener;

use App\Dashboard\Domain\Event\SiteRecordUpdated;
use App\Dashboard\Infrastructure\EventListener\AlertOnSiteErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AlertOnSiteErrorHandlerTest extends TestCase
{
    public function test_logs_critical_when_status_is_error(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
               ->method('critical')
               ->with(
                   self::stringContains('ERROR'),
                   self::arrayHasKey('site_record_id')
               );

        $handler = new AlertOnSiteErrorHandler($logger);
        $event   = new SiteRecordUpdated(
            siteRecordId: 'id-001',
            name:         'Test Site',
            url:          'https://example.com',
            status:       'error'
        );

        $handler($event);
    }

    public function test_does_nothing_when_status_is_not_error(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('critical');

        $handler = new AlertOnSiteErrorHandler($logger);
        $event   = new SiteRecordUpdated(
            siteRecordId: 'id-001',
            name:         'Test Site',
            url:          'https://example.com',
            status:       'active'
        );

        $handler($event);
    }
}
