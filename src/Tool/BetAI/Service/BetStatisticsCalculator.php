<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Enum\BetMarketType;
use App\Tool\BetAI\Model\BetStatistics;
use App\Tool\BetAI\Model\BetTypeStatistics;
use App\Tool\BetAI\Model\MarketStatistics;
use App\Tool\BetAI\Model\TypeStatistics;
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
        $marketTypeData = [];

        foreach ($allBets as $bet) {
            // Nur Wetten berücksichtigen, die bereits entschieden sind
            if (!in_array($bet->getStatus(), ['WON', 'LOST'])) {
                continue;
            }

            if ($startGameWeek && $bet->getSuggestion()->getGameWeek()->getStartDate() < $startGameWeek->getStartDate()) {
                continue;
            }

            $type = $bet->getSuggestion()->getBetType();
            if (!isset($typeData[$type->value])) {
                $typeData[$type->value] = ['type' => $type, 'total' => 0, 'won' => 0, 'lost' => 0, 'profit' => 0.0, 'confidence' => 0.0];
            }
            $typeData[$type->value]['total']++;

            $marketType = $bet->getSuggestion()->getMarketType();
            if (!isset($marketTypeData[$marketType->value])) {
                $marketTypeData[$marketType->value] = ['type' => $marketType, 'total' => 0, 'won' => 0, 'lost' => 0, 'profit' => 0.0, 'confidence' => 0.0];
            }
            $marketTypeData[$marketType->value]['total']++;

            $totalBets++;
            $confidence = $bet->getSuggestion()->getConfidenceScore();
            $totalConfidenceScore += $confidence;
            $typeData[$type->value]['confidence'] += $confidence;
            $marketTypeData[$marketType->value]['confidence'] += $confidence;

            if ($bet->getStatus() === 'WON') {
                $wonBets++;
                $typeData[$type->value]['won']++;
                $marketTypeData[$marketType->value]['won']++;
                $profit = ($bet->getActualPayout() - $bet->getActualStake());
                $totalProfitLoss += $profit;
                $typeData[$type->value]['profit'] += $profit;
                $marketTypeData[$marketType->value]['profit'] += $profit;
            } else {
                $lostBets++;
                $typeData[$type->value]['lost']++;
                $marketTypeData[$marketType->value]['lost']++;
                $loss = $bet->getActualStake();
                $totalProfitLoss -= $loss;
                $typeData[$type->value]['profit'] -= $loss;
                $marketTypeData[$marketType->value]['profit'] -= $loss;
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
        foreach ($typeData as $data) {
            $betType = $data['type'];
            $typeWinRate = $data['total'] > 0 ? $data['won'] / $data['total'] : 0;
            $typeAvgConfidence = $data['total'] > 0 ? $data['confidence'] / $data['total'] : 0;
            $statsPerType[] = new TypeStatistics(
                $betType,
                new BetTypeStatistics(
                    $data['total'],
                    $data['won'],
                    $data['lost'],
                    $typeWinRate,
                    $data['profit'],
                    $typeAvgConfidence
                )
            );
        }

        $statsPerMarketType = [];
        foreach ($marketTypeData as $data) {
            $marketType = $data['type'];
            $marketWinRate = $data['total'] > 0 ? $data['won'] / $data['total'] : 0;
            $marketAvgConfidence = $data['total'] > 0 ? $data['confidence'] / $data['total'] : 0;
            $statsPerMarketType[] = new MarketStatistics(
                $marketType,
                new BetTypeStatistics(
                    $data['total'],
                    $data['won'],
                    $data['lost'],
                    $marketWinRate,
                    $data['profit'],
                    $marketAvgConfidence
                )
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
            $statsPerType,
            $statsPerMarketType
        );
    }
}
