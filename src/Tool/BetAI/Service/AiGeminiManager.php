<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Entity\AiResponse;
use App\Tool\BetAI\Entity\GameWeek;
use Doctrine\ORM\EntityManagerInterface;

class AiGeminiManager
{
    public function __construct(
        private GeminiService $geminiService,
        private BetSuggestionFactory $betSuggestionFactory,
        private EntityManagerInterface $entityManager
    ) {}

    public function validateAiResponse(AiResponse $aiResponse): bool
    {
        $isValid = $this->betSuggestionFactory->validateJson($aiResponse->rawResponse);

        if ($isValid !== $aiResponse->hasValidData) {
            $aiResponse->hasValidData = $isValid;
            $this->entityManager->flush();
        }

        return $isValid;
    }

    public function generateAndPersist(GameWeek $gameWeek, string $startDate, string $endDate): AiResponse
    {
        $rawResponse = $this->geminiService->generateBetPredictions($startDate, $endDate);

        // Versuchen zu parsen, um zu prüfen, ob valide Daten enthalten sind
        $isValid = $this->betSuggestionFactory->createSuggestionsFromJson($rawResponse, $gameWeek->id);

        $aiResponse = new AiResponse($gameWeek, $rawResponse, $isValid);
        if ($isValid) {
            $aiResponse->isProcessed = true;
        }

        $this->entityManager->persist($aiResponse);
        $this->entityManager->flush();

        return $aiResponse;
    }

    public function generateRawAndPersist(GameWeek $gameWeek, string $startDate, string $endDate): AiResponse
    {
        $rawResponse = $this->geminiService->generateBetPredictions($startDate, $endDate);

        $aiResponse = new AiResponse($gameWeek, $rawResponse, false);

        $this->entityManager->persist($aiResponse);
        $this->entityManager->flush();

        return $aiResponse;
    }

    public function replaceSuggestionAndPersist(GameWeek $gameWeek, string $problematicBetJson, AiResponse $lastAiResponse, string $reason, array $existingSuggestions = []): AiResponse
    {
        $startDate = $gameWeek->getStartDate()->format('Y-m-d');
        $endDate = $gameWeek->getEndDate()->format('Y-m-d');

        $rawResponse = $this->geminiService->replaceBetSuggestion(
            $startDate,
            $endDate,
            $problematicBetJson,
            $lastAiResponse->rawResponse,
            $reason,
            $existingSuggestions
        );

        // Wir setzen hasValidData initial auf false, die Factory wird es validieren
        $aiResponse = new AiResponse($gameWeek, $rawResponse, false);

        $this->entityManager->persist($aiResponse);
        $this->entityManager->flush();

        return $aiResponse;
    }
}
