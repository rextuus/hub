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
}
