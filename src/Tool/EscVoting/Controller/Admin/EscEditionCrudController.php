<?php

namespace App\Tool\EscVoting\Controller\Admin;

use App\Tool\EscVoting\Entity\EscEdition;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class EscEditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EscEdition::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('year', 'Jahr');
        yield TextField::new('location', 'Ort');
        yield DateField::new('date', 'Datum');
        yield BooleanField::new('isActive', 'Aktiv');
        yield BooleanField::new('isClosed', 'Abgeschlossen');
    }
}
