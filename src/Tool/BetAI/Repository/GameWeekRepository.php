<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\GameWeek;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameWeek>
 */
class GameWeekRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameWeek::class);
    }

    /**
     * @return GameWeek[]
     */
    public function findGameWeeksWithoutSuggestions(): array
    {
        return $this->createQueryBuilder('gw')
            ->leftJoin('App\Tool\BetAI\Entity\BetSuggestion', 'bs', 'WITH', 'bs.gameWeek = gw')
            ->where('bs.id IS NULL')
            ->getQuery()
            ->getResult();
    }
}
