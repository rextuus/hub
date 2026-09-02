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
        $suggestions = $suggestionRepository->findBy(['gameWeek' => $gameWeek]);

        // Sort suggestions by earliest match date
        usort($suggestions, function (BetSuggestion $a, BetSuggestion $b) {
            $dateA = $a->getEarliestMatchDate();
            $dateB = $b->getEarliestMatchDate();

            if ($dateA === $dateB) {
                return 0;
            }

            if ($dateA === null) {
                return 1;
            }

            if ($dateB === null) {
                return -1;
            }

            return $dateA <=> $dateB;
        });

        return $this->render('tool/bet_ai/gameweek/show.html.twig', [
            'gameWeek' => $gameWeek,
            'suggestions' => $suggestions,
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

        if (!$aiResponse) {
            $this->addFlash('error', 'KI-Antwort nicht gefunden.');
            return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
        }

        if ($aiResponse->isProcessed) {
            $this->addFlash('warning', 'Diese Antwort wurde bereits verarbeitet.');
            return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
        }

        if ($aiResponse) {
            $isValid = $betSuggestionFactory->createSuggestionsFromJson($aiResponse->rawResponse, $gameWeek->id);
            if ($isValid) {
                $aiResponse->hasValidData = true;
                $aiResponse->isProcessed = true;
                $entityManager->flush();
                $this->addFlash('success', 'Wettvorschläge erfolgreich generiert.');
            } else {
                $this->addFlash('error', 'Fehler beim Parsen der KI-Antwort.');
            }
        }

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
    }

    #[Route('/{id}/suggestion/{suggestionId}/toggle-selection', name: 'toggle_selection', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleSelection(GameWeek $gameWeek, int $suggestionId, BetSuggestionRepository $suggestionRepository, EntityManagerInterface $entityManager): Response
    {
        $suggestion = $suggestionRepository->find($suggestionId);
        if (!$suggestion || $suggestion->getGameWeek() !== $gameWeek) {
            throw $this->createNotFoundException();
        }

        $suggestion->setIsSelected(!$suggestion->isSelected());
        $entityManager->flush();

        return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->getId()]);
    }

    #[Route('/{id}/calculate', name: 'calculate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function calculate(
        GameWeek $gameWeek,
        BetSuggestionRepository $suggestionRepository,
        BetStakeCalculator $betStakeCalculator
    ): Response {
        $suggestions = $suggestionRepository->findBy(['gameWeek' => $gameWeek]);

        $missingActualOdds = false;
        foreach ($suggestions as $suggestion) {
            if ($suggestion->getConfidenceScore() >= 6 && $suggestion->getActualOdds() === null) {
                $missingActualOdds = true;
                break;
            }
        }

        $betStakeCalculator->calculateAndSaveStakes($suggestions);

        if ($missingActualOdds) {
            $this->addFlash('info', 'Einsätze wurden berechnet, teilweise basierend auf AI-Quoten (da keine reale Quote eingetragen war).');
        } else {
            $this->addFlash('success', 'Einsätze wurden basierend auf den realen Quoten berechnet.');
        }

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

    #[Route('/{id}/suggestion/{suggestionId}/replace', name: 'replace_suggestion', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function replaceSuggestion(
        GameWeek $gameWeek,
        int $suggestionId,
        BetSuggestionRepository $suggestionRepository,
        AiResponseRepository $aiResponseRepository,
        AiGeminiManager $aiGeminiManager,
        BetSuggestionFactory $betSuggestionFactory,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $suggestion = $suggestionRepository->find($suggestionId);
        if (!$suggestion || $suggestion->getGameWeek() !== $gameWeek) {
            throw $this->createNotFoundException();
        }

        $lastAiResponse = $aiResponseRepository->findOneBy(['gameWeek' => $gameWeek], ['createdAt' => 'ASC']);
        if (!$lastAiResponse) {
            $this->addFlash('error', 'Keine AI-Historie gefunden.');
            return $this->redirectToRoute('app_bet_ai_gameweek_show', ['id' => $gameWeek->id]);
        }

        $reason = $request->request->get('reason', 'Quote weicht zu stark ab.');
        if ($suggestion->getActualOdds()) {
            $diff = abs($suggestion->getActualOdds() - $suggestion->getTotalOdds());
            if ($diff > 0.5) {
                $reason = "Die reale Quote ({$suggestion->getActualOdds()}) weicht stark von der AI-Quote ({$suggestion->getTotalOdds()}) ab.";
            }
        }

        // Problematic Bet JSON erstellen
        $matches = [];
        foreach ($suggestion->getSuggestionMatchItems() as $matchItem) {
            $match = $matchItem->getMatch();
            $matches[] = [
                'home_team' => $match->getHomeTeam() ? $match->getHomeTeam()->getName() : $match->getRawHomeTeamName(),
                'away_team' => $match->getAwayTeam() ? $match->getAwayTeam()->getName() : $match->getRawAwayTeamName(),
                'match_date' => $match->getMatchDate()->format('Y-m-d H:i'),
            ];
        }

        $problematicBetJson = json_encode([
            'type' => $suggestion->getBetType()->value,
            'market' => $suggestion->getMarket(),
            'prediction' => $suggestion->getPrediction(),
            'total_odds' => $suggestion->getTotalOdds(),
            'actual_odds' => $suggestion->getActualOdds(),
            'matches_count' => count($matches),
            'matches' => $matches,
        ]);

        // Bestehende andere Vorschläge sammeln
        $existingSuggestionsDescriptions = [];
        $otherSuggestions = $suggestionRepository->findBy(['gameWeek' => $gameWeek]);
        foreach ($otherSuggestions as $other) {
            if ($other->getId() === $suggestion->getId()) {
                continue;
            }

            $otherMatches = [];
            foreach ($other->getSuggestionMatchItems() as $mItem) {
                $m = $mItem->getMatch();
                $otherMatches[] = ($m->getHomeTeam() ? $m->getHomeTeam()->getName() : $m->getRawHomeTeamName()) . " vs " .
                                ($m->getAwayTeam() ? $m->getAwayTeam()->getName() : $m->getRawAwayTeamName());
            }

            $existingSuggestionsDescriptions[] = sprintf(
                "Typ: %s, Markt: %s, Vorhersage: %s, Spiele: %s",
                $other->getBetType()->value,
                $other->getMarket(),
                $other->getPrediction(),
                implode(", ", $otherMatches)
            );
        }

        try {
            $newAiResponse = $aiGeminiManager->replaceSuggestionAndPersist(
                $gameWeek,
                $problematicBetJson,
                $lastAiResponse,
                $reason,
                $existingSuggestionsDescriptions
            );

            if ($betSuggestionFactory->replaceSuggestionFromJson($newAiResponse->rawResponse, $suggestion)) {
                $newAiResponse->hasValidData = true;
                $newAiResponse->isProcessed = true;
                $entityManager->flush();
                $this->addFlash('success', 'Wett-Vorschlag wurde erfolgreich ausgetauscht.');
            } else {
                $this->addFlash('error', 'Der neue Vorschlag konnte nicht verarbeitet werden.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Fehler beim Austausch: ' . $e->getMessage());
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
