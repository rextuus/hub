<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Controller\Admin;

use App\Tool\Fotokalender\Entity\PhotoCalendar;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<PhotoCalendar> */
class PhotoCalendarCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoCalendar::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Foto-Kalender')
            ->setEntityLabelInPlural('Foto-Kalender')
            ->setDefaultSort(['year' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $show = Action::new('show', 'Ansehen', 'fa fa-external-link-alt')
            ->linkToUrl(fn (PhotoCalendar $c): string => $this->generateUrl('app_fotokalender_calendar', ['year' => $c->getYear()]))
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions->add(Crud::PAGE_INDEX, $show)->add(Crud::PAGE_EDIT, $show);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IntegerField::new('year', 'Jahr');
        yield TextField::new('title', 'Titel');
        yield TextareaField::new('intro', 'Einleitung')->setNumOfRows(3)->hideOnIndex()
            ->setHelp('Ein Satz über den Monatsblättern (leer: „Tippe auf einen Monat …“).');
        yield BooleanField::new('published', 'Veröffentlicht')->setHelp('Sonst nur für Admins sichtbar.');
        yield AssociationField::new('months', 'Monate')->onlyOnIndex();
    }
}
