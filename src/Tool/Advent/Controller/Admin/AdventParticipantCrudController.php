<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller\Admin;

use App\Tool\Advent\Entity\AdventParticipant;
use App\Tool\Advent\Service\AdventLinks;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @extends AbstractCrudController<AdventParticipant> */
class AdventParticipantCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdventLinks $links,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AdventParticipant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Teilnehmer')
            ->setEntityLabelInPlural('Teilnehmer')
            ->setDefaultSort(['calendar' => 'DESC', 'name' => 'ASC'])
            ->setHelp('index', 'Den persönlichen Link an den Teilnehmer schicken – er ist der Zugang, ohne Konto. „Neuer Link“ macht den alten ungültig.');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('calendar');
    }

    public function configureActions(Actions $actions): Actions
    {
        $regenerate = Action::new('regenerateToken', 'Neuer Link', 'fa fa-sync')
            ->linkToCrudAction('regenerateToken')
            ->setHtmlAttributes(['onclick' => "return confirm('Der bisherige Link funktioniert danach nicht mehr. Fortfahren?')"]);

        return $actions->add(Crud::PAGE_INDEX, $regenerate)->add(Crud::PAGE_EDIT, $regenerate);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('calendar', 'Kalender');
        yield TextField::new('name', 'Name');
        yield TextField::new('token', 'Persönlicher Link')
            ->hideOnForm()
            ->formatValue(fn (?string $token, AdventParticipant $p): string => $this->links->participantUrl($p))
            ->setTemplatePath('admin/advent/participant_link.html.twig');
        yield IntegerField::new('points', 'Punkte')->hideOnForm();
        yield IntegerField::new('answers.count', 'Türen')->hideOnForm();
        yield DateTimeField::new('lastSeenAt', 'Zuletzt da')->hideOnForm();
    }

    #[AdminRoute(path: '/regenerate-token', name: 'regenerateToken', options: ['methods' => ['GET']])]
    public function regenerateToken(AdminContext $context, Request $request, EntityManagerInterface $em): Response
    {
        $participant = $context->getEntity()->getInstance();
        if (!$participant instanceof AdventParticipant) {
            throw $this->createNotFoundException();
        }
        $participant->regenerateToken();
        $em->flush();
        $this->addFlash('success', \sprintf('Neuer Link für %s: %s', $participant->getName(), $this->links->participantUrl($participant)));

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin'));
    }
}
