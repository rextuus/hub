<?php

namespace App\Tool\BetAI\Service;

use App\Tool\BetAI\Enum\BetMarketType;
use App\Tool\BetAI\Enum\BetType;
use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\SuggestionMatchItem;
use App\Tool\BetAI\Repository\BetMatchRepository;
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
        private TeamAliasRepository $teamAliasRepository,
        private BetMatchRepository $betMatchRepository
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
            $this->createSuggestionFromData($betData, $gameWeek);
        }

        $this->entityManager->flush();
        return true;
    }

    public function replaceSuggestionFromJson(string $jsonString, BetSuggestion $oldSuggestion): bool
    {
        $data = json_decode($jsonString, true);
        if (!$this->validateJson($jsonString) || empty($data['suggested_bets'])) {
            return false;
        }

        // Wir nehmen den ersten Vorschlag aus der neuen Antwort als Ersatz
        $betData = $data['suggested_bets'][0];
        $newSuggestion = $this->createSuggestionFromData($betData, $oldSuggestion->getGameWeek());

        if ($newSuggestion) {
            // Wenn der alte Vorschlag bereits platziert war, müssten wir theoretisch mehr tun,
            // aber laut Anforderung geht es um Abweichungen, bevor man platziert (oder man will sie korrigieren).
            // Wir löschen den alten Vorschlag und seine Match-Items.
            $matchesToCheck = [];
            foreach ($oldSuggestion->getSuggestionMatchItems() as $matchItem) {
                $matchesToCheck[] = $matchItem->getMatch();
                $this->entityManager->remove($matchItem);
            }
            $this->entityManager->remove($oldSuggestion);

            // Flush, damit die Referenzen weg sind
            $this->entityManager->flush();

            // Jetzt prüfen wir, ob die Matches noch von anderen Vorschlägen referenziert werden
            foreach ($matchesToCheck as $match) {
                if ($this->betMatchRepository->countReferences($match->id) === 0) {
                    // Match wird nicht mehr referenziert -> löschen
                    $this->entityManager->remove($match);
                }
            }
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    private function createSuggestionFromData(array $betData, $gameWeek): BetSuggestion
    {
        $betType = BetType::tryFrom(strtoupper($betData['type'])) ?? BetType::SINGLE;
        $marketName = $betData['market'];
        $marketType = BetMarketType::fromMarketName($marketName);

        $suggestion = new BetSuggestion(
            $gameWeek,
            $betType,
            $marketName,
            $marketType,
            $betData['prediction'],
            (float) $betData['total_odds'],
            0.0, // suggestedStake wird später berechnet
            $betData['ai_reasoning'],
            (int) $betData['confidence_score']
        );
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

        return $suggestion;
    }
}
