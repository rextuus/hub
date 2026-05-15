<?php

namespace App\Tool\EscVoting\Entity;

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
        public Participant $participant,

        #[ORM\Column]
        public int $points,

        #[ORM\ManyToOne(targetEntity: Voter::class)]
        #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
        public ?Voter $voter = null,

        #[ORM\ManyToOne(targetEntity: Ballot::class, inversedBy: 'votes')]
        #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
        public ?Ballot $ballot = null,

        #[ORM\Column(length: 255, nullable: true)]
        public ?string $sessionId = null,
    ) {
        $this->createdAt = new \DateTimeImmutable();
    }
}
