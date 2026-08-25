<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\PlacedBetRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlacedBetRepository::class)]
#[ORM\Table(name: 'bet_ai_placedbet')]
class PlacedBet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: BetSuggestion::class)]
        #[ORM\JoinColumn(nullable: false)]
        private BetSuggestion $suggestion,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $actualStake,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $actualOdds,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $potentialPayout,

        #[ORM\Column(length: 20)]
        private string $status = 'OPEN',

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
        private ?float $actualPayout = null,

        #[ORM\Column(type: 'datetime')]
        private \DateTimeInterface $placedAt = new \DateTime(),
    ) {
    }

    public function getSuggestion(): BetSuggestion
    {
        return $this->suggestion;
    }

    public function setSuggestion(BetSuggestion $suggestion): self
    {
        $this->suggestion = $suggestion;
        return $this;
    }

    public function getActualStake(): float
    {
        return $this->actualStake;
    }

    public function setActualStake(float $actualStake): self
    {
        $this->actualStake = $actualStake;
        return $this;
    }

    public function getActualOdds(): float
    {
        return $this->actualOdds;
    }

    public function setActualOdds(float $actualOdds): self
    {
        $this->actualOdds = $actualOdds;
        return $this;
    }

    public function getPotentialPayout(): float
    {
        return $this->potentialPayout;
    }

    public function setPotentialPayout(float $potentialPayout): self
    {
        $this->potentialPayout = $potentialPayout;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getActualPayout(): ?float
    {
        return $this->actualPayout;
    }

    public function setActualPayout(?float $actualPayout): self
    {
        $this->actualPayout = $actualPayout;
        return $this;
    }

    public function getPlacedAt(): \DateTimeInterface
    {
        return $this->placedAt;
    }

    public function setPlacedAt(\DateTimeInterface $placedAt): self
    {
        $this->placedAt = $placedAt;
        return $this;
    }
}
