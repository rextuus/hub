<?php

namespace App\Tool\BetAI\Command;

use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Entity\League;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\SuggestionMatchItem;
use App\Tool\BetAI\Entity\Team;
use App\Tool\BetAI\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:betai:export',
    description: 'Exports BetAI data to a JSON file.',
)]
class ExportBetAICommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path', 'betai_export.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $outputPath = $input->getOption('output');

        $io->title('Exporting BetAI Data');

        $data = [
            'leagues' => [],
            'teams' => [],
            'bankroll' => [],
            'gameWeeks' => [],
            'matches' => [],
            'suggestions' => [],
            'placedBets' => [],
            'transactions' => [],
        ];

        // Leagues
        $leagues = $this->entityManager->getRepository(League::class)->findAll();
        foreach ($leagues as $league) {
            $data['leagues'][] = [
                'id' => $league->id,
                'name' => $league->getName(),
                'country' => $league->getCountry(),
                'tier' => $league->getTier(),
            ];
        }

        // Teams
        $teams = $this->entityManager->getRepository(Team::class)->findAll();
        foreach ($teams as $team) {
            $data['teams'][] = [
                'id' => $team->id,
                'name' => $team->getName(),
                'league_id' => $team->getLeague()?->id,
                'isActive' => $team->isActive(),
                'wikipediaUrl' => $team->getWikipediaUrl(),
                'logoSearchUrl' => $team->getLogoSearchUrl(),
                'profileImgUrl' => $team->getProfileImgUrl(),
            ];
        }

        // Bankroll
        $bankrolls = $this->entityManager->getRepository(Bankroll::class)->findAll();
        foreach ($bankrolls as $bankroll) {
            $data['bankroll'][] = [
                'id' => $bankroll->id,
                'totalBalance' => $bankroll->getTotalBalance(),
                'initialBalance' => $bankroll->getInitialBalance(),
                'currency' => $bankroll->getCurrency(),
                'lastUpdated' => $bankroll->getLastUpdated()->format(\DateTimeInterface::ATOM),
            ];
        }

        // GameWeeks
        $gameWeeks = $this->entityManager->getRepository(GameWeek::class)->findAll();
        foreach ($gameWeeks as $gw) {
            $data['gameWeeks'][] = [
                'id' => $gw->id,
                'name' => $gw->getName(),
                'startDate' => $gw->getStartDate()->format(\DateTimeInterface::ATOM),
                'endDate' => $gw->getEndDate()->format(\DateTimeInterface::ATOM),
                'status' => $gw->getStatus(),
            ];
        }

        // Matches
        $matches = $this->entityManager->getRepository(BetMatch::class)->findAll();
        foreach ($matches as $match) {
            $data['matches'][] = [
                'id' => $match->id,
                'gameWeek_id' => $match->getGameWeek()->id,
                'homeTeam_id' => $match->getHomeTeam()?->id,
                'awayTeam_id' => $match->getAwayTeam()?->id,
                'rawHomeTeamName' => $match->getRawHomeTeamName(),
                'rawAwayTeamName' => $match->getRawAwayTeamName(),
                'matchDate' => $match->getMatchDate()->format(\DateTimeInterface::ATOM),
                'status' => $match->getStatus(),
                'resultHome' => $match->getResultHome(),
                'resultAway' => $match->getResultAway(),
                'resultHomeHt' => $match->getResultHomeHt(),
                'resultAwayHt' => $match->getResultAwayHt(),
            ];
        }

        // Suggestions
        $suggestions = $this->entityManager->getRepository(BetSuggestion::class)->findAll();
        foreach ($suggestions as $sug) {
            $matchIds = [];
            foreach ($sug->getSuggestionMatchItems() as $item) {
                $matchIds[] = $item->getMatch()->id;
            }

            $data['suggestions'][] = [
                'id' => $sug->id,
                'gameWeek_id' => $sug->getGameWeek()->id,
                'betType' => $sug->getBetType(),
                'market' => $sug->getMarket(),
                'prediction' => $sug->getPrediction(),
                'totalOdds' => $sug->getTotalOdds(),
                'suggestedStake' => $sug->getSuggestedStake(),
                'aiReasoning' => $sug->getAiReasoning(),
                'confidenceScore' => $sug->getConfidenceScore(),
                'isPlaced' => $sug->isPlaced(),
                'actualOdds' => $sug->getActualOdds(),
                'matchIds' => $matchIds,
            ];
        }

        // PlacedBets
        $placedBets = $this->entityManager->getRepository(PlacedBet::class)->findAll();
        foreach ($placedBets as $pb) {
            $data['placedBets'][] = [
                'id' => $pb->id,
                'suggestion_id' => $pb->getSuggestion()->id,
                'actualStake' => $pb->getActualStake(),
                'actualOdds' => $pb->getActualOdds(),
                'potentialPayout' => $pb->getPotentialPayout(),
                'status' => $pb->getStatus(),
                'actualPayout' => $pb->getActualPayout(),
                'placedAt' => $pb->getPlacedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        // Transactions
        $transactions = $this->entityManager->getRepository(Transaction::class)->findAll();
        foreach ($transactions as $tx) {
            $data['transactions'][] = [
                'id' => $tx->id,
                'bankroll_id' => $tx->getBankroll()->id,
                'placedBet_id' => $tx->getPlacedBet()?->id,
                'type' => $tx->getType()->value,
                'amount' => $tx->getAmount(),
                'description' => $tx->getDescription(),
                'createdAt' => $tx->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        file_put_contents($outputPath, json_encode($data, JSON_PRETTY_PRINT));

        $io->success(sprintf('Data exported to %s', $outputPath));

        return Command::SUCCESS;
    }
}
