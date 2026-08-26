<?php

namespace App\Tool\BetAI\Command;

use App\Entity\Project;
use App\Tool\BetAI\Entity\Bankroll;
use App\Tool\BetAI\Entity\League;
use App\Tool\BetAI\Entity\AiResponse;
use App\Tool\BetAI\Entity\BetMatch;
use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Entity\GameWeek;
use App\Tool\BetAI\Entity\PlacedBet;
use App\Tool\BetAI\Entity\SuggestionMatchItem;
use App\Tool\BetAI\Entity\Team;
use App\Tool\BetAI\Entity\TeamAlias;
use App\Tool\BetAI\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:betai:init',
    description: 'Initializes the BetAI project: ensures project entity exists and seeds initial data.',
)]
class InitBetAICommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initializing BetAI Project');

        // 1. Ensure Project Entity exists
        $projectRepo = $this->entityManager->getRepository(Project::class);
        $project = $projectRepo->findOneBy(['route' => 'app_bet_ai_index']);

        if (!$project) {
            $io->note('Creating Project entity for BetAI...');
            $project = new Project();
            $project->setName('BetAI');
            $project->setDescription('KI-gestützte Sportwetten-Analyse und Bankroll-Management.');
            $project->setIcon('lucide:trending-up');
            $project->setImage('https://images.unsplash.com/photo-1508919893223-40fa3c49ddf7?auto=format&fit=crop&q=80&w=400&h=250');
            $project->setRoute('app_bet_ai_index');
            $this->entityManager->persist($project);
        } else {
            $io->note('Project entity for BetAI already exists.');
        }

        if ($io->confirm('Do you want to clear existing BetAI data (transactions, placed bets, suggestions, matches, game weeks, bankroll, leagues, teams) before seeding?', false)) {
            $io->note('Clearing existing BetAI data...');
            $this->entityManager->createQuery('DELETE FROM ' . Transaction::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . PlacedBet::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . SuggestionMatchItem::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . BetSuggestion::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . AiResponse::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . BetMatch::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . GameWeek::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . TeamAlias::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Team::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . League::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Bankroll::class)->execute();
            $this->entityManager->flush();
        }

        // 2. Ensure Bankroll exists
        $bankroll = $this->entityManager->getRepository(Bankroll::class)->findOneBy([]);
        if (!$bankroll) {
            $io->note('Creating initial Bankroll (100.00 EUR)...');
            $bankroll = new Bankroll(
                totalBalance: 100.00,
                initialBalance: 100.00,
                currency: 'EUR'
            );
            $this->entityManager->persist($bankroll);
        }

        // 3. Seed from CSV
        $csvPath = __DIR__ . '/football_clubs_top5_2026_27.csv';
        if (!file_exists($csvPath)) {
            $io->error(sprintf('CSV file not found at %s', $csvPath));
            return Command::FAILURE;
        }

        $io->note('Seeding Leagues and Teams from CSV...');
        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            // Expected columns: season,country,tier,league,club,club_wikipedia_url,logo_search_url

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 7) continue;

                $country = $data[1];
                $tier = (int)$data[2];
                $leagueName = $data[3];
                $teamName = $data[4];
                $wikiUrl = $data[5];
                $logoUrl = $data[6];

                // Ensure League exists
                $league = $this->entityManager->getRepository(League::class)->findOneBy([
                    'name' => $leagueName,
                    'country' => $country
                ]);

                if (!$league) {
                    $league = new League(
                        name: $leagueName,
                        country: $country
                    );
                    $league->setTier($tier);
                    $this->entityManager->persist($league);
                    $io->writeln(sprintf('  > Created league: %s (%s)', $leagueName, $country));
                    // Flush to ensure we can find it in the next iteration if needed
                    $this->entityManager->flush();
                }

                // Ensure Team exists
                $team = $this->entityManager->getRepository(Team::class)->findOneBy([
                    'name' => $teamName,
                    'league' => $league
                ]);

                if (!$team) {
                    $team = new Team(
                        name: $teamName,
                        league: $league,
                        wikipediaUrl: $wikiUrl,
                        logoSearchUrl: $logoUrl
                    );
                    $this->entityManager->persist($team);
                    $io->writeln(sprintf('    - Created team: %s', $teamName));
                }
            }
            fclose($handle);
        }

        $this->entityManager->flush();

        $io->success('BetAI Project initialized successfully.');

        return Command::SUCCESS;
    }
}
