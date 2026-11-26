<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Media\Admin\MediaImageField;
use App\Tool\Advent\Entity\AdventDoor;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

/** @extends AbstractCrudController<AdventDoor> */
class AdventDoorCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdventDoor::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tür')
            ->setEntityLabelInPlural('Türen')
            ->setDefaultSort(['calendar' => 'DESC', 'number' => 'ASC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['question'])
            ->setHelp('edit', 'Genau eine Antwort als richtig markieren. Die Reaktion (Text, Bild oder YouTube-Link) sieht der Teilnehmer nach seiner Antwort.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('calendar');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('calendar', 'Kalender');
        yield IntegerField::new('number', 'Tür')->setHelp('1–24');
        yield TextareaField::new('question', 'Frage')->setNumOfRows(3);
        yield MediaImageField::new('image', 'Bild zur Frage', 'advent');
        yield IntegerField::new('points', 'Punkte')->setHelp('für die richtige Antwort')->hideOnIndex();
        yield CollectionField::new('choices', 'Antworten')
            ->useEntryCrudForm(AdventChoiceCrudController::class)
            ->setEntryIsComplex()
            ->allowAdd()->allowDelete()
            ->renderExpanded()
            ->setFormTypeOption('by_reference', false)
            ->onlyOnForms();
        yield IntegerField::new('choices.count', 'Antworten')->onlyOnIndex();
    }
}
