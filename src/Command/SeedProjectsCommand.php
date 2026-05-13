<?php

namespace App\Command;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-projects',
    description: 'Seeds dummy projects for the landing page.',
)]
class SeedProjectsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projects = [
            [
                'name' => 'ESC Voting',
                'description' => 'Der ultimative Begleiter für deinen Eurovision Song Contest Abend. Verwalte Länder, gib Stimmen ab und verfolge Live-Ergebnisse mit Leichtigkeit.',
                'icon' => 'lucide:star',
                'image' => 'https://images.unsplash.com/photo-1459749411177-042180ce673f?auto=format&fit=crop&q=80&w=400&h=250',
                'route' => 'app_esc_voting_index',
            ],
            [
                'name' => 'Passwort-Manager',
                'description' => 'Ein einfaches Tool, um deine Passwörter sicher zu speichern und zu verwalten.',
                'icon' => 'lucide:key',
                'image' => 'https://picsum.photos/seed/password/400/250',
            ],
            [
                'name' => 'Bild-Optimierer',
                'description' => 'Komprimiere und optimiere deine Bilder schnell für das Web.',
                'icon' => 'lucide:image',
                'image' => 'https://picsum.photos/seed/image/400/250',
            ],
            [
                'name' => 'JSON-Validator',
                'description' => 'Überprüfe und formatiere deine JSON-Strings mit Leichtigkeit.',
                'icon' => 'lucide:file-json',
                'image' => 'https://picsum.photos/seed/json/400/250',
            ],
            [
                'name' => 'Einheiten-Umrechner',
                'description' => 'Rechne zwischen verschiedenen Maßeinheiten um.',
                'icon' => 'lucide:scale',
                'image' => 'https://picsum.photos/seed/scale/400/250',
            ],
            [
                'name' => 'Code-Snippets',
                'description' => 'Behalte deine meistgenutzten Code-Snippets an einem Ort im Blick.',
                'icon' => 'lucide:code-2',
                'image' => 'https://picsum.photos/seed/code/400/250',
            ],
            [
                'name' => 'To-Do-Liste',
                'description' => 'Organisiere deine täglichen Aufgaben und verpasse keine Deadline mehr.',
                'icon' => 'lucide:check-square',
                'image' => 'https://picsum.photos/seed/todo/400/250',
            ],
        ];

        // Clean up existing projects to avoid duplicates if re-seeding
        $this->entityManager->createQuery('DELETE FROM App\Entity\Project')->execute();

        foreach ($projects as $p) {
            $project = new Project();
            $project->setName($p['name']);
            $project->setDescription($p['description']);
            $project->setIcon($p['icon']);
            $project->setImage($p['image']);
            $project->setRoute($p['route'] ?? null);
            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();

        $io->success('Dummy projects seeded successfully.');

        return Command::SUCCESS;
    }
}
