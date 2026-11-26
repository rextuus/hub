<?php

namespace App\Command;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gleicht die Projekte der Startseite ab (nach Namen): legt fehlende an, aktualisiert vorhandene, entfernt die
 * früheren Dummy-Einträge. Andere Projekte, die in EasyAdmin angelegt wurden, bleiben unberührt.
 * Die Bilder (projekte/…) gehören in den Medienordner (docs/deployment.md, „Medienordner“).
 */
#[AsCommand(
    name: 'app:projects:seed',
    description: 'Gleicht die Projekte der Startseite ab (Debes, Nimbus, Secret Santa, About, Tools).',
)]
class SeedProjectsCommand extends Command
{
    /** Platzhalter aus der Anfangszeit von hub. */
    private const array DUMMIES = ['Passwort-Manager', 'Bild-Optimierer', 'JSON-Validator', 'Einheiten-Umrechner', 'Code-Snippets', 'To-Do-Liste'];

    private const array PROJECTS = [
        [
            'name' => 'Debes',
            'description' => 'Wie viel hattest du mir doch gleich für den Bau dieser Website vorgestreckt? Keine Angst – Debes weiß es noch!',
            'icon' => 'lucide:wallet',
            'url' => 'https://debes.wh-company.de',
            'image' => 'projekte/debes.jpg',
            'imagePosition' => '30% 50%',
            'featured' => true,
            'position' => 10,
        ],
        [
            'name' => 'Nimbus',
            'description' => 'Wann hast du das letzte Mal deine Handyfotos auf Festplatte gesichert? Automatisiere es – mit Nimbus!',
            'icon' => 'lucide:cloud',
            'url' => 'https://wh-company.de/nextcloud',
            'image' => 'projekte/nimbus.jpg',
            'featured' => true,
            'position' => 20,
        ],
        [
            'name' => 'Secret Santa',
            'description' => 'Sich für unzählige Verwandte Geschenke einfallen lassen? Nicht das bekommen, was man gerne hätte? Wichteln 2.0 mit Secret Santa kennt diese Probleme nicht!',
            'icon' => 'lucide:gift',
            'url' => 'https://optatio.wh-company.de',
            'image' => 'projekte/secret_santa.jpg',
            'featured' => true,
            'position' => 30,
        ],
        [
            'name' => 'About',
            'description' => 'Du willst sehen, wie wir arbeiten? Warum wir so zielstrebig sind, dass wir gar keine Strikes brauchen? Unser Foto-Kalender 2022.',
            'icon' => 'lucide:camera',
            'route' => 'app_fotokalender_index',
            'image' => 'projekte/about.jpg',
            'video' => 'projekte/about.mp4',
            'featured' => true,
            'position' => 40,
        ],
        [
            'name' => 'ESC Voting',
            'description' => 'Dein Begleiter für den Eurovision Song Contest: Notizen, eigener Stimmzettel und die Ergebnisse.',
            'icon' => 'lucide:star',
            'route' => 'app_esc_voting_index',
            'position' => 100,
        ],
        [
            'name' => 'BetAI',
            'description' => 'KI-gestützte Analyse und Verwaltung von Sportwetten.',
            'icon' => 'lucide:bot',
            'route' => 'app_bet_ai_index',
            'requiresLogin' => true,
            'position' => 110,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProjectRepository $projects,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $removed = 0;
        foreach ($this->projects->findBy(['name' => self::DUMMIES]) as $dummy) {
            $this->entityManager->remove($dummy);
            ++$removed;
        }

        $rows = [];
        foreach (self::PROJECTS as $data) {
            $project = $this->projects->findOneBy(['name' => $data['name']]);
            $rows[] = [$data['name'], null === $project ? 'neu' : 'aktualisiert'];
            $project ??= new Project();

            $project->setName($data['name'])
                ->setDescription($data['description'])
                ->setIcon($data['icon'])
                ->setRoute($data['route'] ?? null)
                ->setUrl($data['url'] ?? null)
                ->setImage($data['image'] ?? null)
                ->setVideo($data['video'] ?? null)
                ->setImagePosition($data['imagePosition'] ?? null)
                ->setFeatured($data['featured'] ?? false)
                ->setPosition($data['position'])
                ->setRequiresLogin($data['requiresLogin'] ?? false);
            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();

        $io->table(['Projekt', 'Aktion'], $rows);
        $io->success(\sprintf('%d Projekte abgeglichen, %d Platzhalter entfernt.', \count($rows), $removed));

        return Command::SUCCESS;
    }
}
