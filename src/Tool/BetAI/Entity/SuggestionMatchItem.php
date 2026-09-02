<?php

namespace App\Tool\BetAI\Entity;

use App\Tool\BetAI\Repository\SuggestionMatchItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuggestionMatchItemRepository::class)]
#[ORM\Table(name: 'bet_ai_suggestion_match_item')]
class SuggestionMatchItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: BetSuggestion::class, inversedBy: 'suggestionMatchItems')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private BetSuggestion $betSuggestion,

        #[ORM\ManyToOne(targetEntity: BetMatch::class)]
        #[ORM\JoinColumn(nullable: false)]
        private BetMatch $match,
    ) {
    }

    public function getBetSuggestion(): BetSuggestion
    {
        return $this->betSuggestion;
    }

    public function setBetSuggestion(BetSuggestion $betSuggestion): self
    {
        $this->betSuggestion = $betSuggestion;
        return $this;
    }

    public function getMatch(): BetMatch
    {
        return $this->match;
    }

    public function setMatch(BetMatch $match): self
    {
        $this->match = $match;
        return $this;
    }
}
