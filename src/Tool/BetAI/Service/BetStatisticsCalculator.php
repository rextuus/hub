<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Model\BetStatistics;
use App\Tool\BetAI\Model\BetTypeStatistics;
use App\Tool\BetAI\Repository\PlacedBetRepository;

class BetStatisticsCalculator
{
    public function __construct(
        private PlacedBetRepository $placedBetRepository
    ) {}

    public function calculateStatistics(?GameWeek $startGameWeek = null): BetStatistics
    {
        $allBets = $this->placedBetRepository->findAll();

        $totalBets = 0;
        $wonBets = 0;
        $lostBets = 0;
        $totalPredictedOdds = 0.0;
        $totalActualOdds = 0.0;
        $totalProfitLoss = 0.0;
        $totalConfidenceScore = 0.0;

        $betsWithActualOdds = 0;
        $typeData = [];

        foreach ($allBets as $bet) {
            // Nur Wetten berücksichtigen, die bereits entschieden sind
            if (!in_array($bet->getStatus(), ['WON', 'LOST'])) {
                continue;
            }

            if ($startGameWeek && $bet->getSuggestion()->getGameWeek()->getStartDate() < $startGameWeek->getStartDate()) {
                continue;
            }

            $type = $bet->getSuggestion()->getBetType()->value;
            if (!isset($typeData[$type])) {
                $typeData[$type] = ['total' => 0, 'won' => 0, 'lost' => 0, 'profit' => 0.0, 'confidence' => 0.0];
            }
            $typeData[$type]['total']++;

            $totalBets++;
            $confidence = $bet->getSuggestion()->getConfidenceScore();
            $totalConfidenceScore += $confidence;
            $typeData[$type]['confidence'] += $confidence;

            if ($bet->getStatus() === 'WON') {
                $wonBets++;
                $typeData[$type]['won']++;
                $profit = ($bet->getActualPayout() - $bet->getActualStake());
                $totalProfitLoss += $profit;
                $typeData[$type]['profit'] += $profit;
            } else {
                $lostBets++;
                $typeData[$type]['lost']++;
                $loss = $bet->getActualStake();
                $totalProfitLoss -= $loss;
                $typeData[$type]['profit'] -= $loss;
            }

            $totalPredictedOdds += $bet->getSuggestion()->getTotalOdds();
            $totalActualOdds += $bet->getActualOdds();
            $betsWithActualOdds++;
        }

        $winRate = $totalBets > 0 ? $wonBets / $totalBets : 0;
        $avgPredictedOdds = $betsWithActualOdds > 0 ? $totalPredictedOdds / $betsWithActualOdds : 0;
        $avgActualOdds = $betsWithActualOdds > 0 ? $totalActualOdds / $betsWithActualOdds : 0;
        $avgConfidenceScore = $totalBets > 0 ? $totalConfidenceScore / $totalBets : 0;

        $statsPerType = [];
        foreach ($typeData as $type => $data) {
            $typeWinRate = $data['total'] > 0 ? $data['won'] / $data['total'] : 0;
            $typeAvgConfidence = $data['total'] > 0 ? $data['confidence'] / $data['total'] : 0;
            $statsPerType[$type] = new BetTypeStatistics(
                $data['total'],
                $data['won'],
                $data['lost'],
                $typeWinRate,
                $data['profit'],
                $typeAvgConfidence
            );
        }

        return new BetStatistics(
            $totalBets,
            $wonBets,
            $lostBets,
            $winRate,
            $avgPredictedOdds,
            $avgActualOdds,
            $totalProfitLoss,
            $avgConfidenceScore,
            $statsPerType
        );
    }
}
