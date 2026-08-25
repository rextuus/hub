<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\PlacedBet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlacedBet>
 */
class PlacedBetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlacedBet::class);
    }
}
