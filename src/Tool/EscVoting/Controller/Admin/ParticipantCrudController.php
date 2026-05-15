<?php

namespace App\Tool\EscVoting\Controller\Admin;

use App\Tool\EscVoting\Entity\Participant;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ParticipantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Participant::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('edition', 'ESC Edition'),
            AssociationField::new('country', 'Land'),
            TextField::new('artist', 'Künstler'),
            TextField::new('song', 'Lied'),
            IntegerField::new('startOrder', 'Startnummer'),
        ];
    }
}
