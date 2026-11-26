<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Tool\Advent\Entity\AdventWinStage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<AdventWinStage> */
class AdventWinStageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdventWinStage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Gewinnstufe')
            ->setEntityLabelInPlural('Gewinnstufen')
            ->setDefaultSort(['calendar' => 'DESC', 'minPoints' => 'ASC'])
            ->setHelp('index', 'Nicht erreichte Gewinne sehen Teilnehmer nur mit verwürfelten Buchstaben.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('calendar');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('calendar', 'Kalender');
        yield IntegerField::new('minPoints', 'Ab Punkten');
        yield TextField::new('label', 'Anzeige')->setHelp('z. B. „5–12 Punkte“');
        yield TextField::new('prize', 'Gewinn');
    }
}
