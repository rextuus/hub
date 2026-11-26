<?php

declare(strict_types=1);

namespace App\Tool\Advent\Repository;

use App\Tool\Advent\Entity\AdventDoor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdventDoor>
 */
class AdventDoorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdventDoor::class);
    }
}
