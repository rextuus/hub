<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\AiResponse;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class AiResponseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AiResponse::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('gameWeek'),
            TextareaField::new('rawResponse'),
            BooleanField::new('hasValidData'),
            BooleanField::new('isProcessed'),
            DateTimeField::new('createdAt')->hideOnForm(),
        ];
    }
}
