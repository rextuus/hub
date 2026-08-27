<?php

namespace App\Tool\BetAI\Controller;

use App\Tool\BetAI\Entity\AiResponse;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Repository\AiResponseRepository;
use App\Tool\BetAI\Repository\BetSuggestionRepository;
use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Repository\PlacedBetRepository;
use App\Tool\BetAI\Service\AiGeminiManager;
use App\Tool\BetAI\Service\BetStakeCalculator;
use App\Tool\BetAI\Service\BetSuggestionFactory;
use App\Tool\BetAI\Service\BetTransactionManager;
use App\Tool\BetAI\Util\DateUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tool/bet-ai/gameweek', name: 'app_bet_ai_gameweek_')]
#[IsGranted('ROLE_USER')]
class BetAIGameWeekController extends AbstractController
{
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(GameWeek $gameWeek, BetSuggestionRepository $suggestionRepository, AiResponseRepository $aiResponseRepository): Response
    {
        return $this->render('tool/bet_ai/gameweek/show.html.twig', [
            'gameWeek' => $gameWeek,
            'suggestions' => $suggestionRepository->findBy(['gameWeek' => $gameWeek]),
            'aiResponses' => $aiResponseRepository->findBy(['gameWeek' => $gameWeek], ['createdAt' => 'DESC']),
            'defaultDates' => DateUtils::getNextWeekendRange(),
        ]);
    }

    #[Route('/{id}/generate', name: 'generate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function generate(
        GameWeek $gameWeek,
        Request $request,
        AiGeminiManager $aiGeminiManager
    ): Response {
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');

        if ($startDate && $endDate) {
            $aiGeminiManager->generateAndPersist($gameWeek, $startDate, $endDate);
        }

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/validate/{responseId}', name: 'validate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function validateResponse(
        GameWeek $gameWeek,
        int $responseId,
        AiResponseRepository $aiResponseRepository,
        AiGeminiManager $aiGeminiManager
    ): Response {
        $aiResponse = $aiResponseRepository->find($responseId);

        if ($aiResponse) {
            $isValid = $aiGeminiManager->validateAiResponse($aiResponse);
            if ($isValid) {
                $this->addFlash('success', 'Rohdaten sind valide.');
            } else {
                $this->addFlash('error', 'Rohdaten sind ungültig.');
            }
        }

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/evaluate/{responseId}', name: 'evaluate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function evaluate(
        GameWeek $gameWeek,
        int $responseId,
        AiResponseRepository $aiResponseRepository,
        BetSuggestionFactory $betSuggestionFactory,
        EntityManagerInterface $entityManager
    ): Response {
        $aiResponse = $aiResponseRepository->find($responseId);

        if ($aiResponse) {
            $isValid = $betSuggestionFactory->createSuggestionsFromJson($aiResponse->rawResponse, $gameWeek->id);
            if ($isValid) {
                $aiResponse->hasValidData = true;
                $entityManager->flush();
                $this->addFlash('success', 'Wettvorschläge erfolgreich generiert.');
            } else {
                $this->addFlash('error', 'Fehler beim Parsen der KI-Antwort.');
            }
        }

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/calculate', name: 'calculate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function calculate(
        GameWeek $gameWeek,
        BetSuggestionRepository $suggestionRepository,
        BetStakeCalculator $betStakeCalculator
    ): Response {
        $suggestions = $suggestionRepository->findBy(['gameWeek' => $gameWeek]);

        $betStakeCalculator->calculateAndSaveStakes($suggestions);

        $this->addFlash('success', 'Einsätze wurden berechnet.');

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/suggestion/{suggestionId}/place', name: 'place_suggestion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function placeSuggestion(
        GameWeek $gameWeek,
        int $suggestionId,
        BetSuggestionRepository $suggestionRepository,
        EntityManagerInterface $entityManager,
        BetTransactionManager $betTransactionManager,
        Request $request
    ): Response {
        $suggestion = $suggestionRepository->find($suggestionId);
        if (!$suggestion || $suggestion->getGameWeek() !== $gameWeek) {
            throw $this->createNotFoundException();
        }

        $suggestion->setIsPlaced(true);
        $actualOdds = $request->request->get('actualOdds');
        if ($actualOdds !== null) {
            $suggestion->setActualOdds((float)$actualOdds);
        }

        $placedBet = new PlacedBet(
            $suggestion,
            $suggestion->getSuggestedStake(),
            $actualOdds ? (float)$actualOdds : $suggestion->getTotalOdds(),
            $suggestion->getSuggestedStake() * ($actualOdds ? (float)$actualOdds : $suggestion->getTotalOdds())
        );

        $entityManager->persist($placedBet);
        $betTransactionManager->placeBet($placedBet);

        $entityManager->flush();

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/suggestion/{suggestionId}/update-odds', name: 'update_actual_odds', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateActualOdds(
        GameWeek $gameWeek,
        int $suggestionId,
        BetSuggestionRepository $suggestionRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $suggestion = $suggestionRepository->find($suggestionId);
        if (!$suggestion || $suggestion->getGameWeek() !== $gameWeek) {
            throw $this->createNotFoundException();
        }

        $actualOdds = $request->request->get('actualOdds');
        if ($actualOdds !== null) {
            $suggestion->setActualOdds((float)$actualOdds);
            $entityManager->flush();
            $this->addFlash('success', 'Reale Quote aktualisiert.');
        }

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/placed-bet/{placedBetId}/finalize', name: 'finalize_bet', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function finalizeBet(
        GameWeek $gameWeek,
        int $placedBetId,
        PlacedBetRepository $placedBetRepository,
        Request $request,
        BetTransactionManager $betTransactionManager
    ): Response {
        $placedBet = $placedBetRepository->find($placedBetId);
        if (!$placedBet || $placedBet->getSuggestion()->getGameWeek() !== $gameWeek) {
            throw $this->createNotFoundException();
        }

        $payout = (float) $request->request->get('payout', 0.0);
        $betTransactionManager->finalizeBet($placedBet, $payout);

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/test-raw', name: 'test_raw', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function testRaw(
        GameWeek $gameWeek,
        Request $request,
        AiGeminiManager $aiGeminiManager
    ): Response {
        $startDate = $request->request->get('startDate');
        $endDate = $request->request->get('endDate');

        $aiResponse = $aiGeminiManager->generateRawAndPersist($gameWeek, $startDate, $endDate);

        return $this->render('tool/bet_ai/gameweek/test_raw.html.twig', [
            'gameWeek' => $gameWeek,
            'rawResponse' => $aiResponse->rawResponse
        ]);
    }

    #[Route('/{id}/view-raw/{responseId}', name: 'view_raw', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function viewRaw(
        GameWeek $gameWeek,
        int $responseId,
        AiResponseRepository $aiResponseRepository
    ): Response {
        $aiResponse = $aiResponseRepository->find($responseId);
        if (!$aiResponse) {
            throw $this->createNotFoundException();
        }
        return $this->render('tool/bet_ai/gameweek/test_raw.html.twig', [
            'gameWeek' => $gameWeek,
            'rawResponse' => $aiResponse->rawResponse
        ]);
    }

    #[Route('/{id}/test-raw-input', name: 'test_raw_input', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function testRawInput(GameWeek $gameWeek): Response
    {
        return $this->render('tool/bet_ai/gameweek/test_raw_input.html.twig', [
            'gameWeek' => $gameWeek,
            'defaultDates' => DateUtils::getNextWeekendRange(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $range = DateUtils::getNextWeekendRange();

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $startDate = $request->request->get('startDate');
            $endDate = $request->request->get('endDate');

            if ($name && $startDate && $endDate) {
                $gameWeek = new GameWeek(
                    $name,
                    new \DateTime($startDate),
                    new \DateTime($endDate)
                );
                $entityManager->persist($gameWeek);
                $entityManager->flush();

                return $this->redirectToRoute('app_bet_ai_index');
            }
        }

        return $this->render('tool/bet_ai/gameweek/new.html.twig', [
            'startDate' => $range['startDate'],
            'endDate' => $range['endDate'],
        ]);
    }
}
