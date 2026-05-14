<?php

namespace App\Tool\EscVoting\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'esc_voting_ballot')]
class Ballot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'ballot', targetEntity: Vote::class, cascade: ['persist', 'remove'])]
    public Collection $votes;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Voter::class)]
        #[ORM\JoinColumn(nullable: false)]
        public Voter $voter
    ) {
        $this->createdAt = new \DateTimeImmutable();
        $this->votes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf('Ballot #%d by %s', $this->id, (string) $this->voter);
    }
}
