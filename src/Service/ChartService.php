<?php

namespace App\Service;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Enum\TransactionType;

class ChartService
{
    /**
     * Prepares data for the Bankroll history chart.
     */
    public function getBankrollHistoryData(Bankroll $bankroll, array $transactions): array
    {
        $currentBalance = $bankroll->getInitialBalance();
        $history = [];

        $history[] = [
            'date' => 'Start',
            'balance' => (float)$currentBalance
        ];

        foreach ($transactions as $transaction) {
            if ($transaction->getType() === TransactionType::CREDIT) {
                $currentBalance += $transaction->getAmount();
            } else {
                $currentBalance -= $transaction->getAmount();
            }
            $history[] = [
                'date' => $transaction->getCreatedAt()->format('d.m. H:i'),
                'balance' => (float)$currentBalance
            ];
        }

        return $history;
    }
}
