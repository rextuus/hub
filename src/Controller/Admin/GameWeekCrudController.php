<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\GameWeek;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class GameWeekCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GameWeek::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            DateTimeField::new('startDate'),
            DateTimeField::new('endDate'),
            ChoiceField::new('status')->setChoices([
                'Planned' => 'PLANNED',
                'Active' => 'ACTIVE',
                'Finished' => 'FINISHED',
            ]),
        ];
    }
}
