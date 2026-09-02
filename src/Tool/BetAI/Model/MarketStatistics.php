<?php

namespace App\Tool\BetAI\Model;

use App\Tool\BetAI\Enum\BetMarketType;

class MarketStatistics
{
    public function __construct(
        public BetMarketType $marketType,
        public BetTypeStatistics $statistics
    ) {}
}
