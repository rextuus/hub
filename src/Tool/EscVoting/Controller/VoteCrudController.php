<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Entity\Vote;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VoteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vote::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('country'),
            IntegerField::new('points'),
            AssociationField::new('voter'),
            TextField::new('sessionId'),
            DateTimeField::new('createdAt')->hideOnForm(),
        ];
    }
}
