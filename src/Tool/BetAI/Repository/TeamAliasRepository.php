<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\TeamAlias;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamAlias>
 */
class TeamAliasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamAlias::class);
    }
}
