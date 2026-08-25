<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\Bankroll;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bankroll>
 */
class BankrollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bankroll::class);
    }
}
