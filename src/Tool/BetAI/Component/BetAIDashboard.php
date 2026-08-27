<?php

namespace App\Tool\BetAI\Component;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Repository\BankrollRepository;
use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Repository\PlacedBetRepository;
use App\Tool\BetAI\Repository\TransactionRepository;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use App\Tool\BetAI\Enum\TransactionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('bet_ai_dashboard', template: 'tool/bet_ai/components/dashboard.html.twig')]
class BetAIDashboard extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private BankrollRepository $bankrollRepository,
        private GameWeekRepository $gameWeekRepository,
        private TransactionRepository $transactionRepository,
        private PlacedBetRepository $placedBetRepository,
        private BetSuggestionRepository $betSuggestionRepository
    ) {
    }

    public function getBankroll(): ?Bankroll
    {
        return $this->bankrollRepository->findOneBy([]);
    }

    public function getGameWeeks(): array
    {
        return $this->gameWeekRepository->findBy([], ['startDate' => 'DESC']);
    }

    public function getTransactions(): array
    {
        return $this->transactionRepository->findBy([], ['createdAt' => 'DESC'], 5);
    }

    public function getOpenBetsValue(): float
    {
        $openBets = $this->placedBetRepository->findBy(['status' => 'OPEN']);
        $total = 0.0;
        foreach ($openBets as $bet) {
            $total += $bet->getActualStake();
        }
        return $total;
    }

    public function getAverageStake(): float
    {
        $bets = $this->placedBetRepository->findAll();
        if (empty($bets)) {
            return 0.0;
        }

        $totalStake = 0.0;
        foreach ($bets as $bet) {
            $totalStake += $bet->getActualStake();
        }

        return $totalStake / count($bets);
    }

    public function getAverageProfit(): float
    {
        $settledBets = $this->placedBetRepository->createQueryBuilder('b')
            ->where('b.status IN (:statuses)')
            ->setParameter('statuses', ['WON', 'LOST'])
            ->getQuery()
            ->getResult();

        if (empty($settledBets)) {
            return 0.0;
        }

        $totalProfit = 0.0;
        foreach ($settledBets as $bet) {
            if ($bet->getStatus() === 'WON') {
                $totalProfit += ($bet->getActualPayout() - $bet->getActualStake());
            } else {
                $totalProfit -= $bet->getActualStake();
            }
        }

        return $totalProfit / count($settledBets);
    }

    public function getGameWeekStats(): array
    {
        $gameWeeks = $this->getGameWeeks();
        $stats = [];

        foreach ($gameWeeks as $gw) {
            $stats[$gw->id ?? 0] = $this->calculateGameWeekProfit($gw);
        }

        return $stats;
    }

    public function getBankrollHistory(): array
    {
        $transactions = $this->transactionRepository->findBy([], ['createdAt' => 'ASC']);
        $history = [];
        $currentBalance = 0.0;

        // Assume initial bankroll is the first transaction or we start at 0
        // If we have a bankroll entity, it might have an initial balance.
        $bankroll = $this->getBankroll();
        if ($bankroll) {
            $currentBalance = $bankroll->getInitialBalance();
            $history[] = [
                'date' => 'Start',
                'balance' => (float)$currentBalance
            ];
        }

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

    private function calculateGameWeekProfit($gameWeek): float
    {
        $suggestions = $this->betSuggestionRepository->findBy(['gameWeek' => $gameWeek]);
        $profit = 0.0;

        foreach ($suggestions as $suggestion) {
            $placedBets = $this->placedBetRepository->findBy(['suggestion' => $suggestion]);
            foreach ($placedBets as $bet) {
                if ($bet->getStatus() === 'WON') {
                    $profit += ($bet->getActualPayout() - $bet->getActualStake());
                } elseif ($bet->getStatus() === 'LOST') {
                    $profit -= $bet->getActualStake();
                }
            }
        }

        return (float)$profit;
    }
}
