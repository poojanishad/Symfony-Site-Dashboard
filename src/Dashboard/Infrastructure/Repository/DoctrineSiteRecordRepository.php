<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Repository;

use App\Dashboard\Domain\Entity\SiteRecord;
use App\Dashboard\Domain\Repository\SiteRecordRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineSiteRecordRepository implements SiteRecordRepositoryInterface
{
    /** @var EntityRepository<SiteRecord> */
    private EntityRepository $repo;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        $this->repo = $em->getRepository(SiteRecord::class);
    }

    public function findById(string $id): ?SiteRecord
    {
        return $this->repo->find($id);
    }

    /** @return SiteRecord[] */
    public function findAll(): array
    {
        return $this->repo->findBy([], ['createdAt' => 'DESC']);
    }

    /** @return SiteRecord[] */
    public function findByStatus(string $status): array
    {
        return $this->repo->findBy(['status' => $status], ['createdAt' => 'DESC']);
    }

    public function save(SiteRecord $siteRecord): void
    {
        $this->em->persist($siteRecord);
        $this->em->flush();
    }

    public function delete(SiteRecord $siteRecord): void
    {
        $this->em->remove($siteRecord);
        $this->em->flush();
    }

    public function nextIdentity(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
