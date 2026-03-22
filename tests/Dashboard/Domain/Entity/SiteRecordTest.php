<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Domain\Entity;

use App\Dashboard\Domain\Entity\SiteRecord;
use App\Dashboard\Domain\Event\SiteRecordCreated;
use App\Dashboard\Domain\Event\SiteRecordDeleted;
use App\Dashboard\Domain\Event\SiteRecordUpdated;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use App\Dashboard\Domain\ValueObject\SiteUrl;
use PHPUnit\Framework\TestCase;

final class SiteRecordTest extends TestCase
{
    private function makeRecord(): SiteRecord
    {
        return SiteRecord::create(
            id:     'test-id-001',
            name:   'Test Site',
            url:    new SiteUrl('https://example.com'),
            status: SiteStatus::pending(),
            notes:  'Initial notes'
        );
    }

    public function test_create_emits_domain_event(): void
    {
        $record = $this->makeRecord();
        $events = $record->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(SiteRecordCreated::class, $events[0]);
        self::assertSame('test-id-001', $events[0]->siteRecordId);
        self::assertSame('Test Site', $events[0]->name);
    }

    public function test_pull_domain_events_clears_queue(): void
    {
        $record = $this->makeRecord();
        $record->pullDomainEvents();

        self::assertCount(0, $record->pullDomainEvents());
    }

    public function test_update_emits_updated_event(): void
    {
        $record = $this->makeRecord();
        $record->pullDomainEvents();

        $record->updateDetails(
            name:   'Updated Site',
            url:    new SiteUrl('https://updated.com'),
            status: SiteStatus::active(),
            notes:  null
        );

        $events = $record->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SiteRecordUpdated::class, $events[0]);
        self::assertSame('Updated Site', $events[0]->name);
    }

    public function test_mark_deleted_emits_deleted_event(): void
    {
        $record = $this->makeRecord();
        $record->pullDomainEvents();
        $record->markDeleted();

        $events = $record->pullDomainEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(SiteRecordDeleted::class, $events[0]);
        self::assertSame('test-id-001', $events[0]->siteRecordId);
    }

    public function test_record_response_time(): void
    {
        $record = $this->makeRecord();
        $record->recordResponseTime(250);

        self::assertSame(250, $record->getResponseTimeMs());
    }

    public function test_record_response_time_rejects_negative(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Response time cannot be negative');

        $this->makeRecord()->recordResponseTime(-1);
    }
}
