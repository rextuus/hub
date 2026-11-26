<?php

declare(strict_types=1);

namespace App\Tool\Advent\Repository;

use App\Tool\Advent\Entity\AdventAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdventAnswer>
 */
class AdventAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdventAnswer::class);
    }
}
