<?php

namespace App\Tool\BetAI\Model;

class BetStatistics
{
    public function __construct(
        public int $totalBets,
        public int $wonBets,
        public int $lostBets,
        public float $winRate,
        public float $avgPredictedOdds,
        public float $avgActualOdds,
        public float $totalProfitLoss,
        public float $avgConfidenceScore,
        public array $statsPerType, // list of TypeStatistics
        public array $statsPerMarketType // list of MarketStatistics
    ) {}

    public function formatForAi(): string
    {
        $typeStats = "";
        foreach ($this->statsPerType as $typeStatsObj) {
            $type = $typeStatsObj->betType->getLabel();
            $stats = $typeStatsObj->statistics;
            $typeStats .= sprintf(
                "\n  - %s: %d Wetten, Gewinnrate %.2f%%, P/L: %.2f, Ø-Confidence: %.2f",
                $type,
                $stats->totalBets,
                $stats->winRate * 100,
                $stats->totalProfitLoss,
                $stats->avgConfidenceScore
            );
        }

        $marketStats = "";
        foreach ($this->statsPerMarketType as $marketStatsObj) {
            $market = $marketStatsObj->marketType->getLabel();
            $stats = $marketStatsObj->statistics;
            $marketStats .= sprintf(
                "\n  - %s: %d Wetten, Gewinnrate %.2f%%, P/L: %.2f, Ø-Confidence: %.2f",
                $market,
                $stats->totalBets,
                $stats->winRate * 100,
                $stats->totalProfitLoss,
                $stats->avgConfidenceScore
            );
        }

        return sprintf(
            "Statistik bisheriger Wetten:\n" .
            "- Anzahl Wetten: %d\n" .
            "- Gewinnrate: %.2f%%\n" .
            "- Ø Prognostizierte Quote: %.2f\n" .
            "- Ø Tatsächliche Quote: %.2f\n" .
            "- Ø Confidence Score: %.2f\n" .
            "- Gesamtbilanz (Profit/Loss): %.2f%s%s",
            $this->totalBets,
            $this->winRate * 100,
            $this->avgPredictedOdds,
            $this->avgActualOdds,
            $this->avgConfidenceScore,
            $this->totalProfitLoss,
            $typeStats,
            $marketStats
        );
    }
}
