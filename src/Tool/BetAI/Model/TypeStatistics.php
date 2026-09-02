<?php

namespace App\Tool\BetAI\Model;

use App\Tool\BetAI\Enum\BetType;

class TypeStatistics
{
    public function __construct(
        public BetType $betType,
        public BetTypeStatistics $statistics
    ) {}
}
