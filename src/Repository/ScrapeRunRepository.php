<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ScrapeRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScrapeRun>
 */
class ScrapeRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScrapeRun::class);
    }

    public function findLatest(): ?ScrapeRun
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
