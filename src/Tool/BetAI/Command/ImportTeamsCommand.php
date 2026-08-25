<?php

namespace App\Tool\BetAI\Command;

use App\Tool\BetAI\Entity\League;
use App\Tool\BetAI\Entity\Team;
use App\Tool\BetAI\Repository\LeagueRepository;
use App\Tool\BetAI\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:bet-ai:import-teams', description: 'Importiert Teams aus einer CSV-Datei')]
class ImportTeamsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LeagueRepository $leagueRepository,
        private TeamRepository $teamRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvFile = __DIR__ . '/football_clubs_top5_2026_27.csv';

        if (!file_exists($csvFile)) {
            $io->error('CSV-Datei nicht gefunden: ' . $csvFile);
            return Command::FAILURE;
        }

        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            $io->error('Konnte CSV-Datei nicht öffnen.');
            return Command::FAILURE;
        }

        // Überspringen der Header-Zeile
        fgetcsv($handle);

        $count = 0;
        while (($data = fgetcsv($handle)) !== false) {
            // CSV-Struktur: season,country,tier,league,club,club_wikipedia_url,logo_search_url
            [$season, $country, $tier, $leagueName, $clubName, $wikipediaUrl, $logoSearchUrl] = $data;

            // Finde oder Erstelle League
            $league = $this->leagueRepository->findOneBy(['name' => $leagueName, 'country' => $country]);
            if (!$league) {
                $league = new League($leagueName, $country);
                $league->setTier((int)$tier);
                $this->entityManager->persist($league);
                $this->entityManager->flush(); // Flush, damit die ID gesetzt ist
            }

            // Finde oder Erstelle Team
            $team = $this->teamRepository->findOneBy(['name' => $clubName]);
            if (!$team) {
                $team = new Team($clubName, $league);
                $this->entityManager->persist($team);
            } else {
                $team->setLeague($league);
            }

            $team->setWikipediaUrl($wikipediaUrl);
            $team->setLogoSearchUrl($logoSearchUrl);

            $count++;
        }

        $this->entityManager->flush();
        fclose($handle);

        $io->success(sprintf('%d Teams erfolgreich importiert/aktualisiert.', $count));

        return Command::SUCCESS;
    }
}
