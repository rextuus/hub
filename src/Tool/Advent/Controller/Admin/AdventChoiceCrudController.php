<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Media\Admin\MediaImageField;
use App\Tool\Advent\Entity\AdventChoice;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

/**
 * Antworten – meist eingebettet in der Tür (AdventDoorCrudController), hier zusätzlich als eigene Liste.
 *
 * @extends AbstractCrudController<AdventChoice>
 */
class AdventChoiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdventChoice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Antwort')
            ->setEntityLabelInPlural('Antworten')
            ->setDefaultSort(['door' => 'ASC', 'position' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('door');
    }

    public function configureFields(string $pageName): iterable
    {
        // Eingebettet in der Tür ist die Zuordnung klar; nur in der eigenen Liste zeigen.
        yield AssociationField::new('door', 'Tür')->hideWhenCreating()->hideWhenUpdating();
        yield TextField::new('text', 'Antwort');
        yield BooleanField::new('correct', 'Richtig')->renderAsSwitch(false);
        yield IntegerField::new('position', 'Position')->setHelp('Reihenfolge (A, B, C …)')->hideOnIndex();
        yield TextareaField::new('response', 'Reaktion')->setNumOfRows(2)->hideOnIndex()
            ->setHelp('Text nach dem Antworten, z. B. „Genau! Das war 2019 in …“');
        yield MediaImageField::new('responseImage', 'Bild zur Reaktion', 'advent')->hideOnIndex();
        yield UrlField::new('responseVideoUrl', 'YouTube-Video')->hideOnIndex()
            ->setHelp('Statt Bild: Link wie https://www.youtube.com/watch?v=… oder https://youtu.be/…');
    }
}
