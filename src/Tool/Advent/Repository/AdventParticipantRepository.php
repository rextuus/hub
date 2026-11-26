<?php

declare(strict_types=1);

namespace App\Tool\Advent\Repository;

use App\Tool\Advent\Entity\AdventParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdventParticipant>
 */
class AdventParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdventParticipant::class);
    }

    /** Teilnehmer samt Kalender, Türen, Antworten und Gewinnstufen für die Kalenderseite. */
    public function findOneByToken(string $token): ?AdventParticipant
    {
        if (1 !== preg_match('/^'.AdventParticipant::TOKEN_PATTERN.'$/', $token)) {
            return null;
        }

        return $this->createQueryBuilder('p')
            ->join('p.calendar', 'c')->addSelect('c')
            ->leftJoin('c.doors', 'd')->addSelect('d')
            ->leftJoin('d.choices', 'ch')->addSelect('ch')
            ->leftJoin('p.answers', 'a')->addSelect('a')
            ->andWhere('p.token = :token')->setParameter('token', $token)
            ->getQuery()->getOneOrNullResult();
    }
}
