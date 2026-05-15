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

    public function findOrCreateVoter(?\App\Entity\User $user, ?string $voterName, string $sessionId): Voter
    {
        if ($user) {
            $voter = $this->findOneBy(['user' => $user]);
            if ($voter) {
                return $voter;
            }
        }

        if ($voterName) {
            $voter = $this->findOneBy(['name' => $voterName, 'sessionId' => $sessionId]);
            if ($voter) {
                return $voter;
            }

            // Try finding by name only (cookie support)
            $voter = $this->findOneBy(['name' => $voterName], ['id' => 'DESC']);
            if ($voter) {
                // Update sessionId
                $voter->sessionId = $sessionId;
                $this->getEntityManager()->flush();
                return $voter;
            }
        }

        $voter = new Voter(
            name: $voterName,
            user: $user,
            sessionId: $sessionId
        );

        $this->getEntityManager()->persist($voter);
        $this->getEntityManager()->flush();

        return $voter;
    }
}
