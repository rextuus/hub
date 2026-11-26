<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Media\Admin\MediaImageField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Projekt')
            ->setEntityLabelInPlural('Projekte')
            ->setDefaultSort(['featured' => 'DESC', 'position' => 'ASC', 'name' => 'ASC'])
            ->setHelp('index', 'Startseite: alle mit „Groß auf der Startseite“ als Streifen, sortiert nach Position. „Alle Projekte“ zeigt alle.');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            MediaImageField::new('image', 'Bild', 'projekte')
                ->setHelp('Kachel auf der Startseite (hochkant und quer zugeschnitten) bzw. Vorschaubild in der Liste. Bei einem Video: das Standbild.'),
            TextField::new('name', 'Name'),
            TextareaField::new('description', 'Beschreibung')->setNumOfRows(3)->hideOnIndex(),
            BooleanField::new('featured', 'Groß auf der Startseite'),
            IntegerField::new('position', 'Position')->setHelp('Kleinere Zahl = weiter vorn.'),
            BooleanField::new('requiresLogin', 'Nur angemeldet')->setHelp('Für Gäste ausgeblendet (z. B. BetAI).'),
            TextField::new('route', 'Route')->setHelp('Internes Tool: Name der Symfony-Route, z. B. app_fotokalender_index')->hideOnIndex(),
            TextField::new('url', 'URL')->setHelp('Externes Projekt: volle Adresse')->hideOnIndex(),
            TextField::new('icon', 'Icon')->setHelp('Lucide-Icon für die Liste ohne Bild, z. B. lucide:star')->hideOnIndex(),
            MediaImageField::video('video', 'Video', 'projekte')
                ->setHelp('Optional, kurzes MP4 ohne Ton (statt Bild auf der Startseite).')->hideOnIndex(),
            TextField::new('imagePosition', 'Bildausschnitt')
                ->setHelp('CSS object-position, z. B. „30% 50%“ (leer = Mitte).')->hideOnIndex(),
        ];
    }
}
