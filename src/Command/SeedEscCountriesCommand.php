<?php

namespace App\Command;

use App\Tool\EscVoting\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-esc-countries',
    description: 'Seeds ESC countries for the voting tool.',
)]
class SeedEscCountriesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $countries = [
            ['name' => 'Sweden', 'code' => 'SE', 'order' => 1],
            ['name' => 'Ukraine', 'code' => 'UA', 'order' => 2],
            ['name' => 'Germany', 'code' => 'DE', 'order' => 3],
            ['name' => 'Luxembourg', 'code' => 'LU', 'order' => 4],
            ['name' => 'Netherlands', 'code' => 'NL', 'order' => 5],
            ['name' => 'Israel', 'code' => 'IL', 'order' => 6],
            ['name' => 'Lithuania', 'code' => 'LT', 'order' => 7],
            ['name' => 'Spain', 'code' => 'ES', 'order' => 8],
            ['name' => 'Estonia', 'code' => 'EE', 'order' => 9],
            ['name' => 'Ireland', 'code' => 'IE', 'order' => 10],
            ['name' => 'Latvia', 'code' => 'LV', 'order' => 11],
            ['name' => 'Greece', 'code' => 'GR', 'order' => 12],
            ['name' => 'United Kingdom', 'code' => 'GB', 'order' => 13],
            ['name' => 'Norway', 'code' => 'NO', 'order' => 14],
            ['name' => 'Italy', 'code' => 'IT', 'order' => 15],
            ['name' => 'Serbia', 'code' => 'RS', 'order' => 16],
            ['name' => 'Finland', 'code' => 'FI', 'order' => 17],
            ['name' => 'Portugal', 'code' => 'PT', 'order' => 18],
            ['name' => 'Armenia', 'code' => 'AM', 'order' => 19],
            ['name' => 'Cyprus', 'code' => 'CY', 'order' => 20],
            ['name' => 'Switzerland', 'code' => 'CH', 'order' => 21],
            ['name' => 'Slovenia', 'code' => 'SI', 'order' => 22],
            ['name' => 'Croatia', 'code' => 'HR', 'order' => 23],
            ['name' => 'Georgia', 'code' => 'GE', 'order' => 24],
            ['name' => 'France', 'code' => 'FR', 'order' => 25],
            ['name' => 'Austria', 'code' => 'AT', 'order' => 26],
        ];

        // Clean up existing countries to avoid duplicates if re-seeding
        $this->entityManager->createQuery('DELETE FROM App\Tool\EscVoting\Entity\Country')->execute();

        foreach ($countries as $data) {
            $country = new Country(
                name: $data['name'],
                countryCode: $data['code'],
                startOrder: $data['order']
            );
            $this->entityManager->persist($country);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Seeded %d ESC countries successfully.', count($countries)));

        return Command::SUCCESS;
    }
}
