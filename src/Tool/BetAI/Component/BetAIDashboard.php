<?php

namespace App\Tool\BetAI\Component;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Repository\BankrollRepository;
use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Repository\PlacedBetRepository;
use App\Tool\BetAI\Repository\TransactionRepository;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use App\Service\ChartService;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Tool\BetAI\Enum\TransactionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('bet_ai_dashboard', template: 'tool/bet_ai/components/dashboard.html.twig')]
class BetAIDashboard extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?int $selectedGameWeekId = null;

    private function getFilteredTransactionQuery(): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->transactionRepository->createQueryBuilder('t');

        if ($this->selectedGameWeekId) {
            $selectedGw = $this->gameWeekRepository->find($this->selectedGameWeekId);
            if ($selectedGw) {
                $query->leftJoin('t.placedBet', 'b')
                      ->leftJoin('b.suggestion', 's')
                      ->leftJoin('s.gameWeek', 'gw')
                      ->where('t.placedBet IS NULL OR gw.startDate >= :startDate')
                      ->setParameter('startDate', $selectedGw->getStartDate());
            }
        }

        return $query;
    }

    private function getStartDate(): ?\DateTimeInterface
    {
        if (!$this->selectedGameWeekId) {
            return null;
        }
        $gw = $this->gameWeekRepository->find($this->selectedGameWeekId);

        $startDate = $gw ? $gw->getStartDate() : null;
        error_log('getStartDate: ID ' . $this->selectedGameWeekId . ' -> date ' . ($startDate ? $startDate->format('Y-m-d H:i:s') : 'null'));

        return $startDate;
    }

    private function shouldIncludeBet(\App\Tool\BetAI\Entity\PlacedBet $bet): bool
    {
        $startDate = $this->getStartDate();
        if (!$startDate) {
            return true;
        }
        return $bet->getSuggestion()->getGameWeek()->getStartDate() >= $startDate;
    }

    public function __construct(
        private BankrollRepository $bankrollRepository,
        private GameWeekRepository $gameWeekRepository,
        private TransactionRepository $transactionRepository,
        private PlacedBetRepository $placedBetRepository,
        private BetSuggestionRepository $betSuggestionRepository,
        private ChartService $chartService,
        private ChartBuilderInterface $chartBuilder
    ) {
    }

    public function getChart(): Chart
    {
        $bankrollHistory = $this->getBankrollHistory();
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);

        $labels = array_map(fn($item) => $item['date'], $bankrollHistory);
        $data = array_map(fn($item) => $item['balance'], $bankrollHistory);

        $borderColor = '#005fb8';
        $backgroundColor = 'rgba(0, 95, 184, 0.1)';

        if (!empty($bankrollHistory)) {
            $initialBalance = $bankrollHistory[0]['balance'];
            $currentBalance = end($bankrollHistory)['balance'];

            if ($currentBalance < $initialBalance) {
                $borderColor = 'rgb(220, 53, 69)';
                $backgroundColor = 'rgba(220, 53, 69, 0.1)';
            } else {
                $borderColor = 'rgb(40, 167, 69)';
                $backgroundColor = 'rgba(40, 167, 69, 0.1)';
            }
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Bankroll',
                    'data' => $data,
                    'borderColor' => $borderColor,
                    'backgroundColor' => $backgroundColor,
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => $borderColor
                ]
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'grid' => ['color' => 'rgba(0, 0, 0, 0.05)']
                ],
                'x' => [
                    'grid' => ['display' => false]
                ]
            ]
        ]);

        return $chart;
    }

    public function getBankroll(): ?Bankroll
    {
        $bankroll = $this->bankrollRepository->findOneBy([]);
        if (!$bankroll) {
            return null;
        }

        $balance = $bankroll->getInitialBalance();

        $transactions = $this->getFilteredTransactionQuery()->getQuery()->getResult();

        foreach ($transactions as $transaction) {
            if ($transaction->getType() === TransactionType::CREDIT) {
                $balance += $transaction->getAmount();
            } else {
                $balance -= $transaction->getAmount();
            }
        }

        $fakeBankroll = new Bankroll();
        $fakeBankroll->setInitialBalance($bankroll->getInitialBalance());
        $fakeBankroll->setTotalBalance($balance);
        $fakeBankroll->setCurrency($bankroll->getCurrency());

        return $fakeBankroll;
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
            if ($this->shouldIncludeBet($bet)) {
                $total += $bet->getActualStake();
            }
        }
        return $total;
    }

    public function getAverageStake(): float
    {
        $bets = $this->placedBetRepository->findAll();
        $filteredBets = array_filter($bets, fn($b) => $this->shouldIncludeBet($b));

        if (empty($filteredBets)) {
            return 0.0;
        }

        $totalStake = 0.0;
        foreach ($filteredBets as $bet) {
            $totalStake += $bet->getActualStake();
        }

        return $totalStake / count($filteredBets);
    }

    public function getAverageProfit(): float
    {
        $settledBets = $this->placedBetRepository->createQueryBuilder('b')
            ->where('b.status IN (:statuses)')
            ->setParameter('statuses', ['WON', 'LOST'])
            ->getQuery()
            ->getResult();

        $filteredBets = array_filter($settledBets, fn($b) => $this->shouldIncludeBet($b));

        if (empty($filteredBets)) {
            return 0.0;
        }

        $totalProfit = 0.0;
        foreach ($filteredBets as $bet) {
            if ($bet->getStatus() === 'WON') {
                $totalProfit += ($bet->getActualPayout() - $bet->getActualStake());
            } else {
                $totalProfit -= $bet->getActualStake();
            }
        }

        return $totalProfit / count($filteredBets);
    }

    public function getGameWeekStats(): array
    {
        $gameWeeks = $this->getGameWeeks();
        $stats = [];

        foreach ($gameWeeks as $gw) {
            $stats[$gw->id ?? 0] = [
                'profit' => $this->calculateGameWeekProfit($gw),
                'avgOdds' => $this->calculateGameWeekAverageOdds($gw),
            ];
        }

        return $stats;
    }

    public function getAverageOdds(): float
    {
        $bets = $this->placedBetRepository->findAll();
        $filteredBets = array_filter($bets, fn($b) => $this->shouldIncludeBet($b));

        if (empty($filteredBets)) {
            return 0.0;
        }

        $totalOdds = 0.0;
        foreach ($filteredBets as $bet) {
            $totalOdds += $bet->getActualOdds();
        }

        return $totalOdds / count($filteredBets);
    }

    public function getBetCounts(): array
    {
        $bets = $this->placedBetRepository->findAll();
        $filteredBets = array_filter($bets, fn($b) => $this->shouldIncludeBet($b));

        $counts = [
            'total' => count($filteredBets),
            'won' => 0,
            'lost' => 0,
            'open' => 0,
        ];

        foreach ($filteredBets as $bet) {
            match ($bet->getStatus()) {
                'WON' => $counts['won']++,
                'LOST' => $counts['lost']++,
                'OPEN' => $counts['open']++,
                default => null,
            };
        }

        return $counts;
    }

    public function getBankrollHistory(): array
    {
        $bankroll = $this->bankrollRepository->findOneBy([]);
        if (!$bankroll) return [];

        if (!$this->selectedGameWeekId) {
            $transactions = $this->transactionRepository
                ->findBy([], ['createdAt' => 'ASC']);
        } else {
            $transactions = $this->getFilteredTransactionQuery()
                ->orderBy('t.createdAt', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->chartService->getBankrollHistoryData($bankroll, $transactions);
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

    private function calculateGameWeekAverageOdds($gameWeek): float
    {
        $suggestions = $this->betSuggestionRepository->findBy(['gameWeek' => $gameWeek]);
        $totalOdds = 0.0;
        $count = 0;

        foreach ($suggestions as $suggestion) {
            $placedBets = $this->placedBetRepository->findBy(['suggestion' => $suggestion]);
            foreach ($placedBets as $bet) {
                $totalOdds += $bet->getActualOdds();
                $count++;
            }
        }

        return $count > 0 ? $totalOdds / $count : 0.0;
    }
}
