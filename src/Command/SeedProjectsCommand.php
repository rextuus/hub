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
                'name' => 'Password Manager',
                'description' => 'A simple tool to store and manage your passwords securely.',
                'icon' => 'lucide:key',
            ],
            [
                'name' => 'Image Optimizer',
                'description' => 'Quickly compress and optimize your images for the web.',
                'icon' => 'lucide:image',
            ],
            [
                'name' => 'JSON Validator',
                'description' => 'Check and format your JSON strings with ease.',
                'icon' => 'lucide:file-json',
            ],
            [
                'name' => 'Unit Converter',
                'description' => 'Convert between different units of measurement.',
                'icon' => 'lucide:scale',
            ],
            [
                'name' => 'Code Snippets',
                'description' => 'Keep track of your most used code snippets in one place.',
                'icon' => 'lucide:code-2',
            ],
            [
                'name' => 'To-Do List',
                'description' => 'Organize your daily tasks and never miss a deadline.',
                'icon' => 'lucide:check-square',
            ],
        ];

        foreach ($projects as $p) {
            $project = new Project();
            $project->setName($p['name']);
            $project->setDescription($p['description']);
            $project->setIcon($p['icon']);
            // Routes don't exist yet, so we leave them null
            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();

        $io->success('Dummy projects seeded successfully.');

        return Command::SUCCESS;
    }
}
