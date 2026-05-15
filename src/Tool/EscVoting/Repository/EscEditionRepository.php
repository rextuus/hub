<?php

namespace App\Tool\EscVoting\Repository;

use App\Tool\EscVoting\Entity\EscEdition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EscEdition>
 */
class EscEditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EscEdition::class);
    }

    public function findActive(): ?EscEdition
    {
        return $this->findOneBy(['isActive' => true]);
    }
}
