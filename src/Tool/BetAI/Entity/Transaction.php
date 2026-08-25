<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\TransactionRepository;
use App\Tool\BetAI\Enum\TransactionType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'bet_ai_transaction')]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bankroll::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Bankroll $bankroll;

    #[ORM\ManyToOne(targetEntity: PlacedBet::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?PlacedBet $placedBet = null;

    #[ORM\Column(type: 'string', enumType: TransactionType::class)]
    private TransactionType $type; // DEBIT or CREDIT

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(length: 255)]
    private string $description;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBankroll(): Bankroll
    {
        return $this->bankroll;
    }

    public function setBankroll(Bankroll $bankroll): self
    {
        $this->bankroll = $bankroll;
        return $this;
    }

    public function getPlacedBet(): ?PlacedBet
    {
        return $this->placedBet;
    }

    public function setPlacedBet(?PlacedBet $placedBet): self
    {
        $this->placedBet = $placedBet;
        return $this;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
