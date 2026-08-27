<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\BetAISetting;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class BetAISettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BetAISetting::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('key'),
            TextareaField::new('value'),
        ];
    }
}
