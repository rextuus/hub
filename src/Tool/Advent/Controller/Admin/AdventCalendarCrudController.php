<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Tool\Advent\Entity\AdventCalendar;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/** @extends AbstractCrudController<AdventCalendar> */
class AdventCalendarCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdventCalendar::class;
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [AdminUrlGenerator::class => AdminUrlGenerator::class]);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Adventskalender')
            ->setEntityLabelInPlural('Adventskalender')
            ->setDefaultSort(['year' => 'DESC'])
            ->setHelp('index', 'Je Kalender: Türen (Fragen + Antworten), Gewinnstufen und Teilnehmer anlegen. Jeder Teilnehmer bekommt einen eigenen Link.');
    }

    public function configureActions(Actions $actions): Actions
    {
        $preview = Action::new('preview', 'Vorschau', 'fa fa-eye')
            ->linkToUrl(fn (AdventCalendar $c): string => $this->generateUrl('app_advent_preview', ['id' => $c->getId()]))
            ->setHtmlAttributes(['target' => '_blank']);
        $doors = Action::new('doors', 'Türen', 'fa fa-door-open')
            ->linkToUrl(fn (AdventCalendar $c): string => $this->filtered(AdventDoorCrudController::class, 'calendar', $c));
        $participants = Action::new('participants', 'Teilnehmer', 'fa fa-users')
            ->linkToUrl(fn (AdventCalendar $c): string => $this->filtered(AdventParticipantCrudController::class, 'calendar', $c));

        return $actions
            ->add(Crud::PAGE_INDEX, $preview)->add(Crud::PAGE_EDIT, $preview)
            ->add(Crud::PAGE_INDEX, $doors)->add(Crud::PAGE_INDEX, $participants);
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Titel')->setHelp('z. B. „Janas Adventskalender“');
        yield IntegerField::new('year', 'Jahr');
        yield DateField::new('startDate', 'Start (Tür 1)')
            ->setHelp('Tür 1 öffnet an diesem Tag um 0 Uhr, jede weitere einen Tag später. Nach Tür 24 bleiben alle offen.');
        yield TimezoneField::new('timezone', 'Zeitzone')->hideOnIndex();
        yield TextareaField::new('intro', 'Einleitung')->setNumOfRows(2)->hideOnIndex();
        yield TextField::new('host', 'Eigene Domain')->hideOnIndex()
            ->setHelp('Optional, z. B. advent.example.com – braucht DNS und einen Eintrag im Caddy auf dem VPS. Leer: Links laufen über wh-company.com.');
        yield AssociationField::new('doors', 'Türen')->onlyOnIndex();
        yield AssociationField::new('participants', 'Teilnehmer')->onlyOnIndex();
    }

    /** @param class-string $crud */
    private function filtered(string $crud, string $field, AdventCalendar $calendar): string
    {
        return $this->container->get(AdminUrlGenerator::class)
            ->setController($crud)->setAction(Action::INDEX)
            ->set('filters', [$field => ['comparison' => '=', 'value' => $calendar->getId()]])
            ->generateUrl();
    }
}
