<?php

namespace App\Tool\BetAI\Model;

class BetTypeStatistics
{
    public function __construct(
        public int $totalBets,
        public int $wonBets,
        public int $lostBets,
        public float $winRate,
        public float $totalProfitLoss,
        public float $avgConfidenceScore
    ) {}
}
