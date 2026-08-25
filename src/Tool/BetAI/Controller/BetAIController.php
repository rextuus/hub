<?php

namespace App\Tool\BetAI\Controller;

use App\Tool\BetAI\Repository\BankrollRepository;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Repository\PlacedBetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class BetAIController extends AbstractController
{
    #[Route('/bet-ai', name: 'app_bet_ai_index', methods: ['GET'])]
    public function index(
        BankrollRepository $bankrollRepository,
        GameWeekRepository $gameWeekRepository,
        BetSuggestionRepository $betSuggestionRepository,
        PlacedBetRepository $placedBetRepository
    ): Response {
        $bankroll = $bankrollRepository->findOneBy([]);
        $gameWeeks = $gameWeekRepository->findBy([], ['startDate' => 'DESC']);

        $gameWeeksData = [];
        foreach ($gameWeeks as $gw) {
            $suggestions = $betSuggestionRepository->findBy(['gameWeek' => $gw]);
            $placedBets = count($suggestions) > 0 ? $placedBetRepository->findBy(['suggestion' => $suggestions]) : [];

            $gameWeeksData[] = [
                'entity' => $gw,
                'suggestionCount' => count($suggestions),
                'placedBetsCount' => count($placedBets),
                'won' => count(array_filter($placedBets, fn($b) => $b->getStatus() === 'WON')),
                'lost' => count(array_filter($placedBets, fn($b) => $b->getStatus() === 'LOST')),
            ];
        }

        $allSuggestions = $betSuggestionRepository->findAll();
        $placedBets = $placedBetRepository->findAll();

        $stats = [
            'totalSuggestions' => count($allSuggestions),
            'totalPlaced' => count($placedBets),
            'won' => count(array_filter($placedBets, fn($b) => $b->getStatus() === 'WON')),
            'lost' => count(array_filter($placedBets, fn($b) => $b->getStatus() === 'LOST')),
        ];

        return $this->render('tool/bet_ai/index.html.twig', [
            'bankroll' => $bankroll,
            'gameWeeksData' => $gameWeeksData,
            'stats' => $stats,
        ]);
    }
}
