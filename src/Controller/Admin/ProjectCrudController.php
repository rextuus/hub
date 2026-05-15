<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProjectCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name', 'Name'),
            TextField::new('description', 'Beschreibung'),
            TextField::new('icon', 'Icon')->setHelp('Lucide icon name (e.g. lucide:key)'),
            TextField::new('image', 'Bild')->setHelp('Image URL'),
            TextField::new('route', 'Route')->setHelp('Symfony route name'),
            TextField::new('url', 'URL')->setHelp('External URL'),
        ];
    }
}
