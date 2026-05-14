<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Entity\Voter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class VoterCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Voter::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            AssociationField::new('user'),
            TextField::new('sessionId'),
        ];
    }
}
