<?php

namespace App\Tool\EscVoting\Controller;

use App\Tool\EscVoting\Entity\Ballot;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BallotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Ballot::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('voter'),
            \EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField::new('createdAt')->hideOnForm(),
            \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('votes')->hideOnForm(),
        ];
    }
}
