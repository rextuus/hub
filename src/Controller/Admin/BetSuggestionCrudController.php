<?php

namespace App\Controller\Admin;

use App\Tool\BetAI\Entity\BetSuggestion;
use App\Tool\BetAI\Enum\BetMarketType;
use App\Tool\BetAI\Enum\BetType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BetSuggestionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BetSuggestion::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('gameWeek'),
            ChoiceField::new('betType')
                ->setChoices(BetType::cases())
                ->setTemplatePath('admin/fields/enum_with_icon.html.twig'),
            TextField::new('market'),
            ChoiceField::new('marketType')
                ->setChoices(BetMarketType::cases())
                ->setTemplatePath('admin/fields/market_type_badge.html.twig'),
            TextField::new('prediction'),
            NumberField::new('totalOdds'),
            NumberField::new('suggestedStake'),
            TextField::new('aiReasoning'),
            NumberField::new('confidenceScore'),
            BooleanField::new('isPlaced'),
            NumberField::new('actualOdds'),
        ];
    }
}
