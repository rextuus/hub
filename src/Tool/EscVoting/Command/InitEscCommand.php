<?php

namespace App\Tool\EscVoting\Command;

use App\Tool\EscVoting\Entity\Participant;
use App\Tool\EscVoting\Entity\EscEdition;
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
            $project->setDescription('Dein Begleiter für den Eurovision Song Contest. Erstelle Notizen, berechne deinen Stimmzettel und verfolge die Ergebnisse.');
            $project->setIcon('lucide:star');
            $project->setImage('https://images.unsplash.com/photo-1459749411177-042180ce673f?auto=format&fit=crop&q=80&w=400&h=250');
            $project->setRoute('app_esc_voting_index');
            $this->entityManager->persist($project);
        } else {
            $io->note('Project entity for ESC Voting already exists.');
        }

        if ($io->confirm('Do you want to clear existing ESC data (votes, ballots, voters, countries, participants, editions) before seeding?', false)) {
            $io->note('Clearing existing ESC data...');
            $this->entityManager->createQuery('DELETE FROM ' . Vote::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Ballot::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Voter::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Participant::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . Country::class)->execute();
            $this->entityManager->createQuery('DELETE FROM ' . EscEdition::class)->execute();
            $this->entityManager->flush();
        }

        // 2.5 Ensure an active edition exists
        $edition = $this->entityManager->getRepository(EscEdition::class)->findOneBy(['isActive' => true]);
        if (!$edition) {
            $io->note('Creating an active ESC Edition (2025)...');
            $edition = new EscEdition();
            $edition->setYear('2025');
            $edition->setLocation('Basel');
            $edition->setIsActive(true);
            $this->entityManager->persist($edition);
            $this->entityManager->flush();
        }

        // 3. Seed Countries (Upsert logic)
        $countryCount = $this->entityManager->getRepository(Country::class)->count([]);
        if ($countryCount === 0 || $io->confirm('Countries already exist. Do you want to update/re-seed them?', false)) {
            $io->note('Seeding countries...');
            $countriesData = [
                ['name' => 'Albania', 'code' => 'AL'],
                ['name' => 'Andorra', 'code' => 'AD'],
                ['name' => 'Armenia', 'code' => 'AM'],
                ['name' => 'Australia', 'code' => 'AU'],
                ['name' => 'Austria', 'code' => 'AT'],
                ['name' => 'Azerbaijan', 'code' => 'AZ'],
                ['name' => 'Belarus', 'code' => 'BY'],
                ['name' => 'Belgium', 'code' => 'BE'],
                ['name' => 'Bosnia and Herzegovina', 'code' => 'BA'],
                ['name' => 'Bulgaria', 'code' => 'BG'],
                ['name' => 'Croatia', 'code' => 'HR'],
                ['name' => 'Cyprus', 'code' => 'CY'],
                ['name' => 'Czechia', 'code' => 'CZ'],
                ['name' => 'Denmark', 'code' => 'DK'],
                ['name' => 'Estonia', 'code' => 'EE'],
                ['name' => 'Finland', 'code' => 'FI'],
                ['name' => 'France', 'code' => 'FR'],
                ['name' => 'Georgia', 'code' => 'GE'],
                ['name' => 'Germany', 'code' => 'DE'],
                ['name' => 'Greece', 'code' => 'GR'],
                ['name' => 'Hungary', 'code' => 'HU'],
                ['name' => 'Iceland', 'code' => 'IS'],
                ['name' => 'Ireland', 'code' => 'IE'],
                ['name' => 'Israel', 'code' => 'IL'],
                ['name' => 'Italy', 'code' => 'IT'],
                ['name' => 'Latvia', 'code' => 'LV'],
                ['name' => 'Lithuania', 'code' => 'LT'],
                ['name' => 'Luxembourg', 'code' => 'LU'],
                ['name' => 'Malta', 'code' => 'MT'],
                ['name' => 'Moldova', 'code' => 'MD'],
                ['name' => 'Monaco', 'code' => 'MC'],
                ['name' => 'Montenegro', 'code' => 'ME'],
                ['name' => 'Morocco', 'code' => 'MA'],
                ['name' => 'Netherlands', 'code' => 'NL'],
                ['name' => 'North Macedonia', 'code' => 'MK'],
                ['name' => 'Norway', 'code' => 'NO'],
                ['name' => 'Poland', 'code' => 'PL'],
                ['name' => 'Portugal', 'code' => 'PT'],
                ['name' => 'Romania', 'code' => 'RO'],
                ['name' => 'Russia', 'code' => 'RU'],
                ['name' => 'San Marino', 'code' => 'SM'],
                ['name' => 'Serbia', 'code' => 'RS'],
                ['name' => 'Slovakia', 'code' => 'SK'],
                ['name' => 'Slovenia', 'code' => 'SI'],
                ['name' => 'Spain', 'code' => 'ES'],
                ['name' => 'Sweden', 'code' => 'SE'],
                ['name' => 'Switzerland', 'code' => 'CH'],
                ['name' => 'Turkey', 'code' => 'TR'],
                ['name' => 'Ukraine', 'code' => 'UA'],
                ['name' => 'United Kingdom', 'code' => 'GB'],
            ];

            $order = 1;
            foreach ($countriesData as $data) {
                $country = $this->entityManager->getRepository(Country::class)->findOneBy(['countryCode' => $data['code']]);
                if (!$country) {
                    $country = new Country(
                        name: $data['name'],
                        countryCode: $data['code']
                    );
                    $this->entityManager->persist($country);
                } else {
                    $country->setName($data['name']);
                }

                // If no participants exist for this edition, seed dummy ones
                $participant = $this->entityManager->getRepository(Participant::class)->findOneBy([
                    'country' => $country,
                    'edition' => $edition
                ]);

                if (!$participant) {
                    $participant = new Participant();
                    $participant->setCountry($country);
                    $participant->setEdition($edition);
                    $participant->setArtist('Artist for ' . $data['name']);
                    $participant->setSong('Song for ' . $data['name']);
                    $participant->setStartOrder($order++);
                    $this->entityManager->persist($participant);
                }
            }
            $this->entityManager->flush();
        }

        $io->success('ESC Project initialized successfully.');

        return Command::SUCCESS;
    }
}
