<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Controller\Admin;

use App\Media\Admin\MediaImageField;
use App\Tool\Fotokalender\Entity\PhotoCalendarPhoto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/** @extends AbstractCrudController<PhotoCalendarPhoto> */
class PhotoCalendarPhotoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoCalendarPhoto::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Foto')
            ->setEntityLabelInPlural('Fotos')
            ->setDefaultSort(['month' => 'ASC', 'position' => 'ASC'])
            ->setPaginatorPageSize(60)
            ->setHelp('index', 'Mehrere Fotos auf einmal: unter Monatsblätter → „Fotos hochladen“. Löschen entfernt auch die Datei.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('month');
    }

    public function configureFields(string $pageName): iterable
    {
        yield MediaImageField::new('image', 'Bild', 'fotokalender');
        yield AssociationField::new('month', 'Monatsblatt');
        yield TextField::new('caption', 'Bildunterschrift')->setHelp('Optional, auch als Alternativtext.');
        yield IntegerField::new('position', 'Position')->setHelp('Kleinere Zahl = weiter vorn.');
    }
}
