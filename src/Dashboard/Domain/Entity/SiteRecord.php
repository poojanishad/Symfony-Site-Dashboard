<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Entity;

use App\Dashboard\Domain\Event\SiteRecordCreated;
use App\Dashboard\Domain\Event\SiteRecordDeleted;
use App\Dashboard\Domain\Event\SiteRecordUpdated;
use App\Dashboard\Domain\ValueObject\SiteStatus;
use App\Dashboard\Domain\ValueObject\SiteUrl;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'site_records')]
#[ORM\HasLifecycleCallbacks]
class SiteRecord
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 500)]
    private string $url;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $responseTimeMs = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        string     $id,
        string     $name,
        SiteUrl    $url,
        SiteStatus $status
    ) {
        $this->id        = $id;
        $this->name      = $name;
        $this->url       = (string) $url;
        $this->status    = (string) $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        string     $id,
        string     $name,
        SiteUrl    $url,
        SiteStatus $status,
        ?string    $notes = null
    ): self {
        $record        = new self($id, $name, $url, $status);
        $record->notes = $notes;

        $record->domainEvents[] = new SiteRecordCreated(
            siteRecordId: $id,
            name: $name,
            url: (string) $url,
            status: (string) $status
        );

        return $record;
    }

    public function updateDetails(
        string     $name,
        SiteUrl    $url,
        SiteStatus $status,
        ?string    $notes = null
    ): void {
        $this->name      = $name;
        $this->url       = (string) $url;
        $this->status    = (string) $status;
        $this->notes     = $notes;
        $this->updatedAt = new \DateTimeImmutable();

        $this->domainEvents[] = new SiteRecordUpdated(
            siteRecordId: $this->id,
            name: $name,
            url: (string) $url,
            status: (string) $status
        );
    }

    public function recordResponseTime(int $milliseconds): void
    {
        if ($milliseconds < 0) {
            throw new \DomainException('Response time cannot be negative.');
        }
        $this->responseTimeMs = $milliseconds;
        $this->updatedAt      = new \DateTimeImmutable();
    }

    public function markDeleted(): void
    {
        $this->domainEvents[] = new SiteRecordDeleted(
            siteRecordId: $this->id,
            name: $this->name
        );
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getUrl(): string
    {
        return $this->url;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getResponseTimeMs(): int
    {
        return $this->responseTimeMs;
    }
    public function getNotes(): ?string
    {
        return $this->notes;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
