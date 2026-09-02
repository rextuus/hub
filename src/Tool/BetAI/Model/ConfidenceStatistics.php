<?php

namespace App\Tool\BetAI\Model;

class ConfidenceStatistics
{
    public function __construct(
        public int $confidenceScore,
        public BetTypeStatistics $statistics
    ) {}
}
