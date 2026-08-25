<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Repository\BankrollRepository;
use Doctrine\ORM\EntityManagerInterface;

class BetStakeCalculator
{
    public function __construct(
        private BankrollRepository $bankrollRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @param BetSuggestion[] $suggestions
     */
    public function calculateAndSaveStakes(array $suggestions): void
    {
        // 1. Hole aktuelle Bankroll
        $bankroll = $this->bankrollRepository->findOneBy([], ['lastUpdated' => 'DESC']);
        if (!$bankroll) {
            return;
        }

        $currentBalance = $bankroll->getTotalBalance();

        // 2. Berechne das maximale Budget für dieses Wochenende
        $maxWeekendBudget = $currentBalance * 0.20;

        // 3. Filtere alle Wetten heraus
        $validSuggestions = array_filter($suggestions, function (BetSuggestion $s) {
            return $s->getConfidenceScore() >= 6;
        });

        if (empty($validSuggestions)) {
            return;
        }

        // 4. Berechne die Summe aller Gewichte (Scores)
        $totalWeight = 0.0;
        foreach ($validSuggestions as $bet) {
            $score = $bet->getConfidenceScore();
            $totalWeight += ($score * $score);
        }

        if ($totalWeight <= 0) {
            return;
        }

        // 5. Berechne den Einsatz für jede einzelne Wette
        foreach ($validSuggestions as $bet) {
            $score = $bet->getConfidenceScore();
            $betWeight = ($score * $score);
            $share = $betWeight / $totalWeight;

            // Exakter Einsatz in Euro
            $calculatedStake = $maxWeekendBudget * $share;

            // Optional: Ein kleiner Mindest- oder Höchstbetrag-Guard (z.B. min. 1€, max. 10€ pro Wette)
            $calculatedStake = max(1.0, min(10.0, $calculatedStake));

            $bet->setSuggestedStake($calculatedStake);
            $this->entityManager->persist($bet);
        }

        $this->entityManager->flush();
    }
}
