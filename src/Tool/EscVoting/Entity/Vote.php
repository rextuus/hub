<?php

namespace App\Tool\EscVoting\Entity;

use App\Entity\User;
use App\Tool\EscVoting\Repository\VoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoteRepository::class)]
#[ORM\Table(name: 'esc_voting_vote')]
class Vote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        public Country $country,

        #[ORM\Column]
        public int $points,

        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: true)]
        public ?User $voter = null,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $sessionId = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }
}
