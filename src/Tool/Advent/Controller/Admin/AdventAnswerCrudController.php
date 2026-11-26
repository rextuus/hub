<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Tool\Advent\Entity\AdventAnswer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * Gegebene Antworten, nur lesen. Löschen setzt eine Tür für den Teilnehmer zurück (er kann neu antworten).
 *
 * @extends AbstractCrudController<AdventAnswer>
 */
class AdventAnswerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdventAnswer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Antwort eines Teilnehmers')
            ->setEntityLabelInPlural('Gegebene Antworten')
            ->setDefaultSort(['answeredAt' => 'DESC'])
            ->setHelp('index', 'Löschen setzt die Tür für diesen Teilnehmer zurück.');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->disable(Action::NEW, Action::EDIT);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('participant')->add('correct');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('participant', 'Teilnehmer');
        yield AssociationField::new('door', 'Tür');
        yield AssociationField::new('choice', 'Antwort');
        yield BooleanField::new('correct', 'Richtig')->renderAsSwitch(false);
        yield IntegerField::new('points', 'Punkte');
        yield DateTimeField::new('answeredAt', 'Wann');
    }
}
