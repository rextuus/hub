<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\BetMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BetMatch>
 */
class BetMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetMatch::class);
    }
}
