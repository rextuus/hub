<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\BankrollRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BankrollRepository::class)]
#[ORM\Table(name: 'bet_ai_bankroll')]
class Bankroll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $totalBalance = 0.0,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $initialBalance = 0.0,

        #[ORM\Column(length: 3)]
        private string $currency = 'EUR',

        #[ORM\Column(type: 'datetime')]
        private \DateTimeInterface $lastUpdated = new \DateTime(),
    ) {
    }

    public function getTotalBalance(): float
    {
        return $this->totalBalance;
    }

    public function setTotalBalance(float $totalBalance): self
    {
        $this->totalBalance = $totalBalance;
        return $this;
    }

    public function getInitialBalance(): float
    {
        return $this->initialBalance;
    }

    public function setInitialBalance(float $initialBalance): self
    {
        $this->initialBalance = $initialBalance;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getLastUpdated(): \DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->totalBalance, $this->currency);
    }
}
