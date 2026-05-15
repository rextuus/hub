<?php

namespace App\Tool\EscVoting\Controller\Admin;

use App\Tool\EscVoting\Entity\Vote;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class VoteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vote::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('ballot', 'Stimmzettel'),
            AssociationField::new('participant', 'Teilnehmer'),
            IntegerField::new('points', 'Punkte'),
            AssociationField::new('voter', 'Voter'),
            TextField::new('sessionId')->onlyOnDetail(),
            DateTimeField::new('createdAt', 'Erstellt am')->hideOnForm(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('ballot'))
            ->add(EntityFilter::new('participant'))
            ->add(EntityFilter::new('voter'));
    }
}
