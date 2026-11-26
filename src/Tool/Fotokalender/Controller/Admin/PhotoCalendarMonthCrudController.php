<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Controller\Admin;

use App\Media\Admin\MediaImageField;
use App\Media\MediaUploader;
use App\Tool\Fotokalender\Entity\PhotoCalendarMonth;
use App\Tool\Fotokalender\Entity\PhotoCalendarPhoto;
use App\Tool\Fotokalender\Model\Month;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @extends AbstractCrudController<PhotoCalendarMonth> */
class PhotoCalendarMonthCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PhotoCalendarMonth::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Monatsblatt')
            ->setEntityLabelInPlural('Monatsblätter')
            ->setDefaultSort(['calendar' => 'DESC', 'month' => 'ASC'])
            ->setSearchFields(['motto']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('calendar');
    }

    public function configureActions(Actions $actions): Actions
    {
        $upload = Action::new('uploadPhotos', 'Fotos hochladen', 'fa fa-images')->linkToCrudAction('uploadPhotos');
        $photos = Action::new('photos', 'Fotos', 'fa fa-th')
            ->linkToUrl(fn (PhotoCalendarMonth $m): string => $this->container->get(AdminUrlGenerator::class)
                ->setController(PhotoCalendarPhotoCrudController::class)->setAction(Action::INDEX)
                ->set('filters', ['month' => ['comparison' => '=', 'value' => $m->getId()]])
                ->generateUrl());

        return $actions
            ->add(Crud::PAGE_INDEX, $upload)->add(Crud::PAGE_EDIT, $upload)
            ->add(Crud::PAGE_INDEX, $photos)->add(Crud::PAGE_EDIT, $photos);
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('calendar', 'Kalender');
        yield ChoiceField::new('month', 'Monat')->setChoices(Month::choices());
        yield TextField::new('motto', 'Motto');
        yield MediaImageField::new('cover', 'Titelbild', 'fotokalender')->setHelp('Das Bild auf dem Kalenderblatt, erstes Bild der Monatsseite.');
        yield AssociationField::new('photos', 'Fotos')->onlyOnIndex();
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [AdminUrlGenerator::class => AdminUrlGenerator::class]);
    }

    #[AdminRoute(path: '/upload-photos', name: 'uploadPhotos', options: ['methods' => ['GET', 'POST']])]
    public function uploadPhotos(AdminContext $context, Request $request, MediaUploader $uploader, EntityManagerInterface $em): Response
    {
        $month = $context->getEntity()->getInstance();
        if (!$month instanceof PhotoCalendarMonth) {
            throw $this->createNotFoundException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('fotokalender_upload_'.$month->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Ungültiges Formular-Token.');
            }
            /** @var list<UploadedFile> $files */
            $files = array_filter((array) $request->files->all('photos'));
            $position = array_reduce($month->getPhotos()->toArray(), static fn (int $max, PhotoCalendarPhoto $p): int => max($max, $p->getPosition()), 0);
            $dir = \sprintf('fotokalender/%d/%s', $month->getCalendar()?->getYear() ?? 0, $month->getSlug());
            $stored = 0;
            foreach ($files as $file) {
                try {
                    $photo = (new PhotoCalendarPhoto())->setImage($uploader->storeImage($file, $dir))->setPosition($position += 10);
                    $month->addPhoto($photo);
                    $em->persist($photo);
                    ++$stored;
                } catch (\InvalidArgumentException $e) {
                    $this->addFlash('danger', $e->getMessage());
                }
            }
            $em->flush();
            if ($stored > 0) {
                $this->addFlash('success', \sprintf('%d Foto(s) zu %s hinzugefügt.', $stored, $month));
            } elseif ([] === $files) {
                $this->addFlash('warning', 'Keine Datei ausgewählt (oder zusammen größer als 100 MB).');
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('admin/fotokalender/upload_photos.html.twig', ['month' => $month]);
    }
}
