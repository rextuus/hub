<?php

namespace App\Tool\EscVoting\Entity;

use App\Entity\User;
use App\Tool\EscVoting\Repository\VoterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoterRepository::class)]
#[ORM\Table(name: 'esc_voting_voter')]
class Voter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(length: 255, nullable: true)]
        public ?string $name = null,

        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: true)]
        public ?User $user = null,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $sessionId = null,
    ) {
    }

    public function __toString(): string
    {
        if ($this->user) {
            return (string) $this->user;
        }

        return $this->name ?? 'Anonymer Voter';
    }
}
