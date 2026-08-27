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

    public function countReferences(int $matchId): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'SELECT COUNT(smi.id) FROM App\Tool\BetAI\Entity\SuggestionMatchItem smi WHERE smi.match = :matchId'
        )
        ->setParameter('matchId', $matchId)
        ->getSingleScalarResult();
    }
}
