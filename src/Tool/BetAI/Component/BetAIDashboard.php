<?php

namespace App\Tool\BetAI\Component;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Repository\BankrollRepository;
use App\Tool\BetAI\Repository\GameWeekRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('bet_ai_dashboard', template: 'tool/bet_ai/components/dashboard.html.twig')]
class BetAIDashboard extends AbstractController
{
    use DefaultActionTrait;

    public function __construct(
        private BankrollRepository $bankrollRepository,
        private GameWeekRepository $gameWeekRepository
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
}
