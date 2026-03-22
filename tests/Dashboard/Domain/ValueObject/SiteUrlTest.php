<?php

declare(strict_types=1);

namespace App\Tests\Dashboard\Domain\ValueObject;

use App\Dashboard\Domain\ValueObject\SiteUrl;
use PHPUnit\Framework\TestCase;

final class SiteUrlTest extends TestCase
{
    public function test_accepts_valid_urls(string $url): void
    {
        $vo = new SiteUrl($url);
        self::assertSame($url, (string) $vo);
    }

    public static function validUrls(): array
    {
        return [
            ['https://example.com'],
            ['http://localhost:8080'],
            ['https://sub.domain.co.uk/path?query=1'],
        ];
    }

    public function test_rejects_invalid_urls(string $url, string $expectedMsg): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMsg);

        new SiteUrl($url);
    }

    public static function invalidUrls(): array
    {
        return [
            ['',              'cannot be empty'],
            ['not-a-url',     'not a valid URL'],
            ['ftp://bad.com', 'must use http or https'],
        ];
    }

    public function test_equality(): void
    {
        $a = new SiteUrl('https://example.com');
        $b = new SiteUrl('https://example.com');
        $c = new SiteUrl('https://other.com');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
