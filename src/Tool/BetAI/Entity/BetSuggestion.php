<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Enum\BetType;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BetSuggestionRepository::class)]
#[ORM\Table(name: 'bet_ai_betsuggestion')]
class BetSuggestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'betSuggestion', targetEntity: SuggestionMatchItem::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $suggestionMatchItems;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: GameWeek::class)]
        #[ORM\JoinColumn(nullable: false)]
        private GameWeek $gameWeek,

        #[ORM\Column(type: 'string', length: 10, enumType: BetType::class)]
        private BetType $betType = BetType::SINGLE,

        #[ORM\Column(length: 255)]
        private string $market = '',

        #[ORM\Column(length: 255)]
        private string $prediction = '',

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $totalOdds = 0.0,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
        private float $suggestedStake = 0.0,

        #[ORM\Column(type: 'text')]
        private string $aiReasoning = '',

        #[ORM\Column]
        private int $confidenceScore = 1,

        #[ORM\Column]
        private bool $isPlaced = false,

        #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
        private ?float $actualOdds = null,
    ) {
        $this->suggestionMatchItems = new ArrayCollection();
    }

    public function getSuggestionMatchItems(): Collection
    {
        return $this->suggestionMatchItems;
    }

    public function addSuggestionMatchItem(SuggestionMatchItem $item): self
    {
        if (!$this->suggestionMatchItems->contains($item)) {
            $this->suggestionMatchItems->add($item);
            $item->setBetSuggestion($this);
        }
        return $this;
    }

    public function removeSuggestionMatchItem(SuggestionMatchItem $item): self
    {
        $this->suggestionMatchItems->removeElement($item);
        return $this;
    }

    // Getters and setters...
    public function getGameWeek(): GameWeek
    {
        return $this->gameWeek;
    }

    public function setGameWeek(GameWeek $gameWeek): self
    {
        $this->gameWeek = $gameWeek;
        return $this;
    }

    public function getBetType(): BetType
    {
        return $this->betType;
    }

    public function setBetType(BetType $betType): self
    {
        $this->betType = $betType;
        return $this;
    }

    public function getMarket(): string
    {
        return $this->market;
    }

    public function setMarket(string $market): self
    {
        $this->market = $market;
        return $this;
    }

    public function getPrediction(): string
    {
        return $this->prediction;
    }

    public function setPrediction(string $prediction): self
    {
        $this->prediction = $prediction;
        return $this;
    }

    public function getTotalOdds(): float
    {
        return $this->totalOdds;
    }

    public function setTotalOdds(float $totalOdds): self
    {
        $this->totalOdds = $totalOdds;
        return $this;
    }

    public function getSuggestedStake(): float
    {
        return $this->suggestedStake;
    }

    public function setSuggestedStake(float $suggestedStake): self
    {
        $this->suggestedStake = $suggestedStake;
        return $this;
    }

    public function getAiReasoning(): string
    {
        return $this->aiReasoning;
    }

    public function setAiReasoning(string $aiReasoning): self
    {
        $this->aiReasoning = $aiReasoning;
        return $this;
    }

    public function getConfidenceScore(): int
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(int $confidenceScore): self
    {
        $this->confidenceScore = $confidenceScore;
        return $this;
    }

    public function isPlaced(): bool
    {
        return $this->isPlaced;
    }

    public function setIsPlaced(bool $isPlaced): self
    {
        $this->isPlaced = $isPlaced;
        return $this;
    }

    public function getActualOdds(): ?float
    {
        return $this->actualOdds;
    }

    public function setActualOdds(?float $actualOdds): self
    {
        $this->actualOdds = $actualOdds;
        return $this;
    }
}
