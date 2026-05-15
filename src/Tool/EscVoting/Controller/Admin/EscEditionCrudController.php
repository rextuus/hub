<?php

namespace App\Tool\EscVoting\Controller\Admin;

use App\Tool\EscVoting\Entity\Country;
use App\Tool\EscVoting\Entity\Participant;
use App\Tool\EscVoting\Entity\EscEdition;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EscEditionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EscEdition::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('year', 'Jahr');
        yield TextField::new('location', 'Ort');
        yield DateField::new('date', 'Datum');
        yield BooleanField::new('isActive', 'Aktiv');
        yield BooleanField::new('isClosed', 'Abgeschlossen');
        yield TextField::new('bannerLink', 'Banner Ziel-Link (URL)');
        yield TextField::new('bannerImage', 'Banner Bild URL');
    }

    public function configureActions(Actions $actions): Actions
    {
        $seedParticipants = Action::new('seedParticipants', 'Teilnehmer importieren', 'fa fa-upload')
            ->linkToCrudAction('seedParticipants');

        return $actions
            ->add(Crud::PAGE_INDEX, $seedParticipants)
            ->add(Crud::PAGE_DETAIL, $seedParticipants);
    }

    #[AdminRoute(path: '/seed-participants', name: 'seedParticipants', options: ['methods' => ['GET', 'POST']])]
    public function seedParticipants(AdminContext $context, Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $edition = $context->getEntity()->getInstance();
        if (!$edition instanceof EscEdition) {
            throw new \RuntimeException('Invalid ESC Edition');
        }

        if ($request->isMethod('POST')) {
            $list = $request->request->get('participant_list');
            $lines = explode("\n", $list);
            $em = $this->container->get('doctrine')->getManager();
            $countryRepo = $em->getRepository(Country::class);

            $errors = [];
            $importedCount = 0;

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $data = str_getcsv($line, ';');
                if (count($data) < 4) {
                    $errors[] = sprintf('Zeile %d: Ungültiges Format (erwartet: CountryCode;Artist;Song;StartOrder)', $index + 1);
                    continue;
                }

                [$countryCode, $artist, $song, $startOrder] = array_map('trim', $data);

                $country = $countryRepo->findOneBy(['countryCode' => $countryCode]);
                if (!$country) {
                    $errors[] = sprintf('Zeile %d: Land mit Code "%s" nicht gefunden.', $index + 1, $countryCode);
                    continue;
                }

                $participant = new Participant();
                $participant->setEdition($edition);
                $participant->setCountry($country);
                $participant->setArtist($artist);
                $participant->setSong($song);
                $participant->setStartOrder((int)$startOrder);

                $em->persist($participant);
                $importedCount++;
            }

            if (empty($errors)) {
                $em->flush();
                $this->addFlash('success', sprintf('%d Teilnehmer wurden erfolgreich importiert.', $importedCount));
                return $this->redirect($adminUrlGenerator->setController(self::class)->setAction(Action::INDEX)->generateUrl());
            }

            foreach ($errors as $error) {
                $this->addFlash('danger', $error);
            }
        }

        return $this->render('admin/esc_voting/seed_participants.html.twig', [
            'edition' => $edition,
        ]);
    }
}
