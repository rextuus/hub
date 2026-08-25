<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\BetSuggestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BetSuggestion>
 */
class BetSuggestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BetSuggestion::class);
    }
}
