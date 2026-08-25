<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\Transaction;
use App\Tool\BetAI\Enum\TransactionType;
use App\Tool\BetAI\Repository\BankrollRepository;
use Doctrine\ORM\EntityManagerInterface;

class BetTransactionManager
{
    public function __construct(
        private BankrollRepository $bankrollRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function placeBet(PlacedBet $bet): void
    {
        $bankroll = $this->getBankroll();
        $stake = $bet->getActualStake();

        $bankroll->setTotalBalance($bankroll->getTotalBalance() - $stake);

        $transaction = new Transaction();
        $transaction->setBankroll($bankroll);
        $transaction->setPlacedBet($bet);
        $transaction->setType(TransactionType::DEBIT);
        $transaction->setAmount($stake);
        $transaction->setDescription('Wette platziert: ' . $bet->getSuggestion()->getPrediction());

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function finalizeBet(PlacedBet $bet, float $payout): void
    {
        $bankroll = $this->getBankroll();

        $bet->setActualPayout($payout);
        $bet->setStatus($payout > 0 ? 'WON' : 'LOST');

        if ($payout > 0) {
            $bankroll->setTotalBalance($bankroll->getTotalBalance() + $payout);

            $transaction = new Transaction();
            $transaction->setBankroll($bankroll);
            $transaction->setPlacedBet($bet);
            $transaction->setType(TransactionType::CREDIT);
            $transaction->setAmount($payout);
            $transaction->setDescription('Wettgewinn: ' . $bet->getSuggestion()->getPrediction());

            $this->entityManager->persist($transaction);
        }

        $this->entityManager->flush();
    }

    private function getBankroll(): Bankroll
    {
        // Assuming there is only one bankroll entry
        $bankroll = $this->bankrollRepository->findAll();
        if (empty($bankroll)) {
            throw new \RuntimeException('Keine Bankroll gefunden.');
        }
        return $bankroll[0];
    }
}
