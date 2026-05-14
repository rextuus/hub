<?php

namespace App\Tool\EscVoting\Repository;

use App\Tool\EscVoting\Entity\Voter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voter>
 */
class VoterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voter::class);
    }
}
