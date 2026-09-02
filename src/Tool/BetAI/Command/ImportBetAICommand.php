<?php

namespace App\Tool\BetAI\Command;

use App\Tool\BetAI\Entity\AiResponse;
use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Entity\BetAISetting;
use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Entity\League;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\SuggestionMatchItem;
use App\Tool\BetAI\Entity\Team;
use App\Tool\BetAI\Entity\TeamAlias;
use App\Tool\BetAI\Entity\Transaction;
use App\Tool\BetAI\Enum\BetType;
use App\Tool\BetAI\Enum\TransactionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:betai:import',
    description: 'Imports BetAI data from a JSON file.',
)]
class ImportBetAICommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'JSON file path to import')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing data before import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error(sprintf('File %s not found.', $filePath));
            return Command::FAILURE;
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!$data) {
            $io->error('Invalid JSON file.');
            return Command::FAILURE;
        }

        $io->title('Importing BetAI Data');

        if ($input->getOption('clear')) {
            $io->note('Clearing existing BetAI data...');
            $this->entityManager->createQuery('DELETE FROM ' . Transaction::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . PlacedBet::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . AiResponse::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . BetAISetting::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . SuggestionMatchItem::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . BetSuggestion::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . BetMatch::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . GameWeek::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . TeamAlias::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Team::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . League::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Bankroll::class)->execute();
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $idMaps = [
            'leagues' => [],
            'teams' => [],
            'bankrolls' => [],
            'gameWeeks' => [],
            'matches' => [],
            'suggestions' => [],
            'placedBets' => [],
        ];

        // 1. Leagues
        $io->section('Importing Leagues');
        foreach ($data['leagues'] ?? [] as $leagueData) {
            $league = new League($leagueData['name'], $leagueData['country']);
            $league->setTier($leagueData['tier']);
            $this->entityManager->persist($league);
            $idMaps['leagues'][$leagueData['id']] = $league;
        }
        $this->entityManager->flush();

        // 2. Teams
        $io->section('Importing Teams');
        foreach ($data['teams'] ?? [] as $teamData) {
            $league = $teamData['league_id'] ? ($idMaps['leagues'][$teamData['league_id']] ?? null) : null;
            $team = new Team(
                name: $teamData['name'],
                league: $league,
                isActive: $teamData['isActive'] ?? true,
                wikipediaUrl: $teamData['wikipediaUrl'] ?? null,
                logoSearchUrl: $teamData['logoSearchUrl'] ?? null
            );
            $team->setProfileImgUrl($teamData['profileImgUrl'] ?? null);
            $this->entityManager->persist($team);
            $idMaps['teams'][$teamData['id']] = $team;
        }
        $this->entityManager->flush();

        // 2.1 TeamAliases
        $io->section('Importing TeamAliases');
        foreach ($data['teamAliases'] ?? [] as $taData) {
            $team = $idMaps['teams'][$taData['team_id']] ?? null;
            if (!$team) continue;

            $alias = new TeamAlias();
            $alias->setRawName($taData['rawName']);
            $alias->setTeam($team);
            $this->entityManager->persist($alias);
        }
        $this->entityManager->flush();

        // 3. Bankroll
        $io->section('Importing Bankroll');
        foreach ($data['bankroll'] ?? [] as $brData) {
            $bankroll = new Bankroll(
                totalBalance: $brData['totalBalance'],
                initialBalance: $brData['initialBalance'],
                currency: $brData['currency'],
                lastUpdated: new \DateTime($brData['lastUpdated'])
            );
            $this->entityManager->persist($bankroll);
            $idMaps['bankrolls'][$brData['id']] = $bankroll;
        }
        $this->entityManager->flush();

        // 4. GameWeeks
        $io->section('Importing GameWeeks');
        foreach ($data['gameWeeks'] ?? [] as $gwData) {
            $gw = new GameWeek(
                $gwData['name'],
                new \DateTime($gwData['startDate']),
                new \DateTime($gwData['endDate']),
                $gwData['status']
            );
            $this->entityManager->persist($gw);
            $idMaps['gameWeeks'][$gwData['id']] = $gw;
        }
        $this->entityManager->flush();

        // 4.1 AiResponses
        $io->section('Importing AiResponses');
        foreach ($data['aiResponses'] ?? [] as $respData) {
            $gw = $idMaps['gameWeeks'][$respData['gameWeek_id']] ?? null;
            if (!$gw) continue;

            $resp = new AiResponse($gw, $respData['rawResponse'], $respData['hasValidData']);
            $resp->createdAt = new \DateTime($respData['createdAt']);
            $resp->isProcessed = $respData['isProcessed'];
            $this->entityManager->persist($resp);
        }
        $this->entityManager->flush();

        // 5. Matches
        $io->section('Importing Matches');
        foreach ($data['matches'] ?? [] as $mData) {
            $gw = $idMaps['gameWeeks'][$mData['gameWeek_id']] ?? null;
            if (!$gw) continue;

            $homeTeam = $mData['homeTeam_id'] ? ($idMaps['teams'][$mData['homeTeam_id']] ?? null) : null;
            $awayTeam = $mData['awayTeam_id'] ? ($idMaps['teams'][$mData['awayTeam_id']] ?? null) : null;

            $match = new BetMatch(
                gameWeek: $gw,
                homeTeam: $homeTeam,
                awayTeam: $awayTeam,
                rawHomeTeamName: $mData['rawHomeTeamName'],
                rawAwayTeamName: $mData['rawAwayTeamName'],
                matchDate: new \DateTime($mData['matchDate']),
                status: $mData['status'],
                resultHome: $mData['resultHome'],
                resultAway: $mData['resultAway'],
                resultHomeHt: $mData['resultHomeHt'],
                resultAwayHt: $mData['resultAwayHt']
            );
            $this->entityManager->persist($match);
            $idMaps['matches'][$mData['id']] = $match;
        }
        $this->entityManager->flush();

        // 6. Suggestions
        $io->section('Importing Suggestions');
        foreach ($data['suggestions'] ?? [] as $sData) {
            $gw = $idMaps['gameWeeks'][$sData['gameWeek_id']] ?? null;
            if (!$gw) continue;

            $sug = new BetSuggestion(
                gameWeek: $gw,
                betType: BetType::from($sData['betType']),
                market: $sData['market'],
                prediction: $sData['prediction'],
                totalOdds: $sData['totalOdds'],
                suggestedStake: $sData['suggestedStake'],
                aiReasoning: $sData['aiReasoning'],
                confidenceScore: $sData['confidenceScore'],
                isPlaced: $sData['isPlaced'],
                actualOdds: $sData['actualOdds']
            );
            $this->entityManager->persist($sug);
            $idMaps['suggestions'][$sData['id']] = $sug;

            // SuggestionMatchItems
            foreach ($sData['matchIds'] ?? [] as $mId) {
                $match = $idMaps['matches'][$mId] ?? null;
                if ($match) {
                    $item = new SuggestionMatchItem($sug, $match);
                    $this->entityManager->persist($item);
                }
            }
        }
        $this->entityManager->flush();

        // 7. PlacedBets
        $io->section('Importing PlacedBets');
        foreach ($data['placedBets'] ?? [] as $pbData) {
            $sug = $idMaps['suggestions'][$pbData['suggestion_id']] ?? null;
            if (!$sug) continue;

            $pb = new PlacedBet(
                suggestion: $sug,
                actualStake: $pbData['actualStake'],
                actualOdds: $pbData['actualOdds'],
                potentialPayout: $pbData['potentialPayout'],
                status: $pbData['status'],
                actualPayout: $pbData['actualPayout'],
                placedAt: new \DateTime($pbData['placedAt'])
            );
            $this->entityManager->persist($pb);
            $idMaps['placedBets'][$pbData['id']] = $pb;
        }
        $this->entityManager->flush();

        // 8. Transactions
        $io->section('Importing Transactions');
        foreach ($data['transactions'] ?? [] as $txData) {
            $bankroll = $idMaps['bankrolls'][$txData['bankroll_id']] ?? null;
            if (!$bankroll) continue;

            $placedBet = $txData['placedBet_id'] ? ($idMaps['placedBets'][$txData['placedBet_id']] ?? null) : null;

            $tx = new Transaction();
            $tx->setBankroll($bankroll);
            $tx->setPlacedBet($placedBet);
            $tx->setType(TransactionType::from($txData['type']));
            $tx->setAmount($txData['amount']);
            $tx->setDescription($txData['description']);
            $tx->setCreatedAt(new \DateTime($txData['createdAt']));

            $this->entityManager->persist($tx);
        }

        // 9. BetAISettings
        $io->section('Importing BetAISettings');
        foreach ($data['betAiSettings'] ?? [] as $sData) {
            $setting = new BetAISetting($sData['key'], $sData['value']);
            $this->entityManager->persist($setting);
        }
        $this->entityManager->flush();

        $io->success('Import completed.');

        return Command::SUCCESS;
    }
}
