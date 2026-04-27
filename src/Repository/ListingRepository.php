<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Listing>
 */
class ListingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Listing::class);
    }

    public function findByExternalId(int $externalId): ?Listing
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    /**
     * @param int[] $externalIds
     * @return Listing[]
     */
    public function findActiveExcept(array $externalIds): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.removedAt IS NULL');

        if ($externalIds !== []) {
            $qb->andWhere('l.externalId NOT IN (:ids)')
                ->setParameter('ids', $externalIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Listing[]
     */
    public function findAllActiveOrderedByPrice(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.removedAt IS NULL')
            ->orderBy('l.currentPrice', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recently-removed listings — candidate predecessors for a freshly-relisted car.
     *
     * @return Listing[]
     */
    public function findRemovedSince(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.removedAt IS NOT NULL')
            ->andWhere('l.removedAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
