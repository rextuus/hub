<?php

namespace App\Tool\BetAI\Component;

use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Repository\PlacedBetRepository;
use App\Tool\BetAI\Repository\SuggestionMatchItemRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('bet_suggestion', template: 'tool/bet_ai/components/bet_suggestion.html.twig')]
class BetSuggestionComponent
{
    public BetSuggestion $suggestion;
    public ?int $index = null;

    public function __construct(
        private SuggestionMatchItemRepository $matchItemRepository,
        private PlacedBetRepository $placedBetRepository
    ) {}

    public function getMatchItems(): array
    {
        return $this->matchItemRepository->findBy(['betSuggestion' => $this->suggestion]);
    }

    public function getPlacedBet(): ?PlacedBet
    {
        return $this->placedBetRepository->findOneBy(['suggestion' => $this->suggestion]);
    }
}
