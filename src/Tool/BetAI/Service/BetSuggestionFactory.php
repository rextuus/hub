<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Enum\BetMarketType;
use App\Tool\BetAI\Enum\BetType;
use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\SuggestionMatchItem;
use App\Tool\BetAI\Repository\GameWeekRepository;
use App\Tool\BetAI\Repository\TeamAliasRepository;
use App\Tool\BetAI\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;

class BetSuggestionFactory
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameWeekRepository $gameWeekRepository,
        private TeamRepository $teamRepository,
        private TeamAliasRepository $teamAliasRepository
    ) {}

    public function validateJson(string $jsonString): bool
    {
        $data = json_decode($jsonString, true);
        return $data && isset($data['suggested_bets']);
    }

    public function createSuggestionsFromJson(string $jsonString, int $gameWeekId): bool
    {
        $data = json_decode($jsonString, true);
        if (!$this->validateJson($jsonString)) {
            return false;
        }

        $gameWeek = $this->gameWeekRepository->find($gameWeekId);

        if (!$gameWeek) {
            return false;
        }

        foreach ($data['suggested_bets'] as $betData) {
            $betType = BetType::tryFrom(strtoupper($betData['type'])) ?? BetType::SINGLE;
            $marketName = $betData['market'];
            $marketType = BetMarketType::fromMarketName($marketName);

            $suggestion = new BetSuggestion(
                $gameWeek,
                $betType,
                $marketName,
                $betData['prediction'],
                (float) $betData['total_odds'],
                0.0, // suggestedStake wird später berechnet
                $betData['ai_reasoning'],
                (int) $betData['confidence_score']
            );
            $suggestion->setMarketType($marketType);
            $this->entityManager->persist($suggestion);

            foreach ($betData['matches'] as $matchData) {
                $homeTeam = $this->teamRepository->findOneBy(['name' => $matchData['home_team']]);
                if (!$homeTeam) {
                    $homeAlias = $this->teamAliasRepository->findOneBy(['rawName' => $matchData['home_team']]);
                    if ($homeAlias) {
                        $homeTeam = $homeAlias->getTeam();
                    }
                }

                $awayTeam = $this->teamRepository->findOneBy(['name' => $matchData['away_team']]);
                if (!$awayTeam) {
                    $awayAlias = $this->teamAliasRepository->findOneBy(['rawName' => $matchData['away_team']]);
                    if ($awayAlias) {
                        $awayTeam = $awayAlias->getTeam();
                    }
                }

                $match = new BetMatch(
                    $gameWeek,
                    $homeTeam,
                    $awayTeam,
                    $matchData['home_team'],
                    $matchData['away_team'],
                    new \DateTime($matchData['match_date'])
                );
                $this->entityManager->persist($match);

                $suggestionMatchItem = new SuggestionMatchItem($suggestion, $match);
                $this->entityManager->persist($suggestionMatchItem);
            }
        }

        $this->entityManager->flush();
        return true;
    }
}
