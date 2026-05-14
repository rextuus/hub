<?php

namespace App\Tool\EscVoting\Command;

use App\Entity\Project;
use App\Tool\EscVoting\Entity\Country;
use App\Tool\EscVoting\Entity\Vote;
use App\Tool\EscVoting\Entity\Ballot;
use App\Tool\EscVoting\Entity\Voter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:esc:init',
    description: 'Initializes the ESC project: ensures project entity exists and seeds initial countries.',
)]
class InitEscCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initializing ESC Project');

        // 1. Ensure Project Entity exists
        $projectRepo = $this->entityManager->getRepository(Project::class);
        $project = $projectRepo->findOneBy(['route' => 'app_esc_voting_index']);

        if (!$project) {
            $io->note('Creating Project entity for ESC Voting...');
            $project = new Project();
            $project->setName('ESC Voting');
            $project->setDescription('Der ultimative Begleiter für deinen Eurovision Song Contest Abend. Verwalte Länder, gib Stimmen ab und verfolge Live-Ergebnisse mit Leichtigkeit.');
            $project->setIcon('lucide:star');
            $project->setImage('https://images.unsplash.com/photo-1459749411177-042180ce673f?auto=format&fit=crop&q=80&w=400&h=250');
            $project->setRoute('app_esc_voting_index');
            $this->entityManager->persist($project);
        } else {
            $io->note('Project entity for ESC Voting already exists.');
        }

        // 2. Clear existing ESC data (optional but recommended for "init")
        if ($io->confirm('Do you want to clear existing ESC data (votes, ballots, voters, countries) before seeding?', true)) {
            $io->note('Clearing existing ESC data...');
            $this->entityManager->createQuery('DELETE FROM ' . Vote::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Ballot::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Voter::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Country::class)->execute();
            $this->entityManager->flush();
        }

        // 3. Seed Countries
        $io->note('Seeding countries...');
        $countriesData = [
            ['name' => 'Schweden', 'code' => 'SE', 'order' => 1],
            ['name' => 'Ukraine', 'code' => 'UA', 'order' => 2],
            ['name' => 'Deutschland', 'code' => 'DE', 'order' => 3],
            ['name' => 'Luxemburg', 'code' => 'LU', 'order' => 4],
            ['name' => 'Niederlande', 'code' => 'NL', 'order' => 5],
            ['name' => 'Israel', 'code' => 'IL', 'order' => 6],
            ['name' => 'Litauen', 'code' => 'LT', 'order' => 7],
            ['name' => 'Spanien', 'code' => 'ES', 'order' => 8],
            ['name' => 'Estland', 'code' => 'EE', 'order' => 9],
            ['name' => 'Irland', 'code' => 'IE', 'order' => 10],
            ['name' => 'Lettland', 'code' => 'LV', 'order' => 11],
            ['name' => 'Griechenland', 'code' => 'GR', 'order' => 12],
            ['name' => 'Großbritannien', 'code' => 'GB', 'order' => 13],
            ['name' => 'Norwegen', 'code' => 'NO', 'order' => 14],
            ['name' => 'Italien', 'code' => 'IT', 'order' => 15],
            ['name' => 'Serbien', 'code' => 'RS', 'order' => 16],
            ['name' => 'Finnland', 'code' => 'FI', 'order' => 17],
            ['name' => 'Portugal', 'code' => 'PT', 'order' => 18],
            ['name' => 'Armenien', 'code' => 'AM', 'order' => 19],
            ['name' => 'Zypern', 'code' => 'CY', 'order' => 20],
            ['name' => 'Schweiz', 'code' => 'CH', 'order' => 21],
            ['name' => 'Slowenien', 'code' => 'SI', 'order' => 22],
            ['name' => 'Kroatien', 'code' => 'HR', 'order' => 23],
            ['name' => 'Georgien', 'code' => 'GE', 'order' => 24],
            ['name' => 'Frankreich', 'code' => 'FR', 'order' => 25],
            ['name' => 'Österreich', 'code' => 'AT', 'order' => 26],
        ];

        foreach ($countriesData as $data) {
            $country = new Country(
                name: $data['name'],
                countryCode: $data['code'],
                startOrder: $data['order']
            );
            $this->entityManager->persist($country);
        }

        $this->entityManager->flush();

        $io->success('ESC Project initialized successfully.');

        return Command::SUCCESS;
    }
}
