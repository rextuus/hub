<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\Bankroll;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BankrollCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Bankroll::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            NumberField::new('totalBalance'),
            NumberField::new('initialBalance'),
            TextField::new('currency'),
            DateTimeField::new('lastUpdated'),
        ];
    }
}
