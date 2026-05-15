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
    name: 'app:esc:seed-countries',
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

        foreach ($countries as $data) {
            $country = $this->entityManager->getRepository(Country::class)->findOneBy(['countryCode' => $data['code']]);
            if (!$country) {
                $country = new Country(
                    name: $data['name'],
                    countryCode: $data['code']
                );
                $this->entityManager->persist($country);
            } else {
                // Update name if it changed
                $country->setName($data['name']);
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Seeded %d ESC countries successfully.', count($countries)));

        return Command::SUCCESS;
    }
}
