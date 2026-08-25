<?php

namespace App\Tool\BetAI\Util;

class DateUtils
{
    /**
     * @return array{startDate: string, endDate: string}
     */
    public static function getNextWeekendRange(): array
    {
        $now = new \DateTime('today');
        $dayOfWeek = (int)$now->format('N'); // 1=Mon, ..., 5=Fri, 6=Sat, 7=Sun

        if ($dayOfWeek >= 5) {
            $startDate = $now->format('Y-m-d');
            $end = clone $now;
            $end->modify('sunday');
            $endDate = $end->format('Y-m-d');
        } else {
            $start = clone $now;
            $start->modify('next friday');
            $startDate = $start->format('Y-m-d');

            $end = clone $start;
            $end->modify('sunday');
            $endDate = $end->format('Y-m-d');
        }

        return ['startDate' => $startDate, 'endDate' => $endDate];
    }
}
