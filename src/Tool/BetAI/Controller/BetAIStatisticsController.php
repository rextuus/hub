<?php

namespace App\Tool\BetAI\Controller;

use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Service\BetStatisticsCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BetAIStatisticsController extends AbstractController
{
    #[Route('/bet-ai/statistics', name: 'app_bet_ai_statistics', methods: ['GET'])]
    public function index(
        BetStatisticsCalculator $calculator,
        GameWeekRepository $gameWeekRepository,
        Request $request
    ): Response {
        $startGameWeekId = $request->query->get('startGameWeekId');
        $startGameWeek = $startGameWeekId ? $gameWeekRepository->find($startGameWeekId) : null;

        $stats = $calculator->calculateStatistics($startGameWeek);

        return $this->render('tool/bet_ai/statistics.html.twig', [
            'stats' => $stats,
            'gameWeeks' => $gameWeekRepository->findBy([], ['startDate' => 'ASC']),
            'selectedGameWeek' => $startGameWeek,
        ]);
    }
}
