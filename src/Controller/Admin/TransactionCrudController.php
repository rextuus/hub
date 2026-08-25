<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\Transaction;
use App\Tool\BetAI\Enum\TransactionType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class TransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Transaction::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('bankroll'),
            AssociationField::new('placedBet'),
            ChoiceField::new('type')->setChoices([
                'Debit' => TransactionType::DEBIT,
                'Credit' => TransactionType::CREDIT,
            ]),
            NumberField::new('amount'),
            TextField::new('description'),
            DateTimeField::new('createdAt'),
        ];
    }
}
