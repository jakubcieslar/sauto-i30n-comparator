<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PriceSnapshot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PriceSnapshot>
 */
class PriceSnapshotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PriceSnapshot::class);
    }
}
