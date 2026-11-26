<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return list<Project> sortiert nach Position, dann Name; ohne Anmeldung ohne die Projekte mit requiresLogin
     */
    public function findVisible(bool $loggedIn, ?bool $featured = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.name', 'ASC');
        if (!$loggedIn) {
            $qb->andWhere('p.requiresLogin = false');
        }
        if (null !== $featured) {
            $qb->andWhere('p.featured = :featured')->setParameter('featured', $featured);
        }

        return $qb->getQuery()->getResult();
    }
}
