<?php

namespace App\Tool\EscVoting\Command;

use App\Tool\EscVoting\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:esc:import',
    description: 'Imports a hardcoded list of countries for ESC voting.',
)]
class ImportCountriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $countriesData = [
            ['name' => 'Deutschland', 'code' => 'DE', 'order' => 1],
            ['name' => 'Frankreich', 'code' => 'FR', 'order' => 2],
            ['name' => 'Italien', 'code' => 'IT', 'order' => 3],
            ['name' => 'Spanien', 'code' => 'ES', 'order' => 4],
            ['name' => 'Vereinigtes Königreich', 'code' => 'GB', 'order' => 5],
            ['name' => 'Ukraine', 'code' => 'UA', 'order' => 6],
            ['name' => 'Schweden', 'code' => 'SE', 'order' => 7],
            ['name' => 'Schweiz', 'code' => 'CH', 'order' => 8],
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

        $io->success('Countries imported successfully.');

        return Command::SUCCESS;
    }
}
