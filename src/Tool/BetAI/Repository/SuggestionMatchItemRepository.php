<?php

namespace App\Tool\BetAI\Repository;

use App\Tool\BetAI\Entity\SuggestionMatchItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuggestionMatchItem>
 */
class SuggestionMatchItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuggestionMatchItem::class);
    }
}
