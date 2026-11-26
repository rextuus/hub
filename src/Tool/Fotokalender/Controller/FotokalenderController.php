<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Controller;

use App\Tool\Fotokalender\Entity\PhotoCalendar;
use App\Tool\Fotokalender\Model\Month;
use App\Tool\Fotokalender\Repository\PhotoCalendarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Foto-Kalender („About“ auf der Startseite): Übersicht der Monatsblätter und eine Seite je Monat.
 * Inhalte und Bilder pflegt der Admin in EasyAdmin (Bereich Fotokalender), die Bilder liegen im Medienordner.
 */
final class FotokalenderController extends AbstractController
{
    /** Jahr der alten Seite wh-company.de/calendar/ (Weiterleitungen). */
    public const int LEGACY_YEAR = 2022;

    public function __construct(
        private readonly PhotoCalendarRepository $calendars,
    ) {
    }

    #[Route('/kalender', name: 'app_fotokalender_index', methods: ['GET'])]
    public function index(): Response
    {
        $visible = $this->calendars->findVisible($this->isGranted('ROLE_ADMIN'));
        if ([] === $visible) {
            throw new NotFoundHttpException('Noch kein Foto-Kalender veröffentlicht.');
        }

        return $this->overview($this->load($visible[0]->getYear()), $visible);
    }

    #[Route('/kalender/{year}', name: 'app_fotokalender_calendar', requirements: ['year' => '\d{4}'], methods: ['GET'])]
    public function calendar(int $year): Response
    {
        return $this->overview($this->load($year), $this->calendars->findVisible($this->isGranted('ROLE_ADMIN')));
    }

    #[Route('/kalender/{year}/{month}', name: 'app_fotokalender_month', requirements: ['year' => '\d{4}', 'month' => '[a-z]+'], methods: ['GET'])]
    public function month(int $year, string $month): Response
    {
        $calendar = $this->load($year);
        $number = Month::fromSlug($month);
        $sheet = null !== $number ? $calendar->getMonth($number) : null;
        if (null === $sheet) {
            throw new NotFoundHttpException();
        }

        $months = $calendar->getMonths()->getValues();
        $index = array_search($sheet, $months, true);

        return $this->render('tool/fotokalender/month.html.twig', [
            'calendar' => $calendar,
            'sheet' => $sheet,
            'previous' => $months[$index - 1] ?? null,
            'next' => $months[$index + 1] ?? null,
        ]);
    }

    /** Alte Adressen (Apache auf securitas leitet wh-company.de/calendar/* hierher weiter). */
    #[Route('/calendar/', name: 'app_fotokalender_legacy_root', methods: ['GET'])]
    #[Route('/calendar/overview.html', name: 'app_fotokalender_legacy_overview', methods: ['GET'])]
    public function legacyOverview(): RedirectResponse
    {
        return $this->redirectToRoute('app_fotokalender_index', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/calendar/detail/{name}.html', name: 'app_fotokalender_legacy_month', requirements: ['name' => '[a-z]+'], methods: ['GET'])]
    public function legacyMonth(string $name): RedirectResponse
    {
        $number = Month::LEGACY_SLUGS[$name] ?? null;
        if (null === $number) {
            return $this->legacyOverview();
        }

        return $this->redirectToRoute('app_fotokalender_month', ['year' => self::LEGACY_YEAR, 'month' => Month::slug($number)], Response::HTTP_MOVED_PERMANENTLY);
    }

    private function load(int $year): PhotoCalendar
    {
        return $this->calendars->findOneWithPhotos($year, $this->isGranted('ROLE_ADMIN'))
            ?? throw new NotFoundHttpException();
    }

    /** @param list<PhotoCalendar> $all */
    private function overview(PhotoCalendar $calendar, array $all): Response
    {
        return $this->render('tool/fotokalender/overview.html.twig', [
            'calendar' => $calendar,
            'calendars' => $all,
        ]);
    }
}
