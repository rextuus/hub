<?php

declare(strict_types=1);

namespace App\Tool\Advent\Controller;

use App\Tool\Advent\Entity\AdventParticipant;
use App\Tool\Advent\Repository\AdventCalendarRepository;
use App\Tool\Advent\Repository\AdventParticipantRepository;
use App\Tool\Advent\Service\AdventGame;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Adventskalender: Teilnehmer kommen über ihren persönlichen Link (/advent/<token>), ohne Konto.
 * Die Routen hängen an keiner Domain; ein Kalender kann deshalb auch auf eigener Domain laufen.
 */
final class AdventController extends AbstractController
{
    #[Route('/advent/{token}', name: 'app_advent_calendar', requirements: ['token' => AdventParticipant::TOKEN_PATTERN], methods: ['GET'])]
    public function calendar(string $token, AdventParticipantRepository $participants, AdventGame $game): Response
    {
        $participant = $participants->findOneByToken($token) ?? throw new NotFoundHttpException();
        $game->touch($participant);

        return $this->private($this->render('tool/advent/calendar.html.twig', [
            'calendar' => $participant->getCalendar(),
            'participant' => $participant,
            'token' => $token,
            'preview' => null,
        ]));
    }

    /** „/“ auf der eigenen Domain eines Kalenders (Bedingung: AdventHosts). */
    #[Route('/', name: 'app_advent_host_home', condition: "service('advent_hosts').isCalendarHost(request.getHost())", methods: ['GET'], priority: -10)]
    public function hostHome(Request $request, AdventCalendarRepository $calendars): Response
    {
        return $this->private($this->render('tool/advent/host_home.html.twig', [
            'calendar' => $calendars->findOneByHost($request->getHost()) ?? throw new NotFoundHttpException(),
        ]));
    }

    #[Route('/admin/advent/{id}/vorschau', name: 'app_advent_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function preview(int $id, AdventCalendarRepository $calendars): Response
    {
        $calendar = $calendars->find($id) ?? throw new NotFoundHttpException();

        return $this->private($this->render('tool/advent/calendar.html.twig', [
            'calendar' => $calendar,
            'participant' => null,
            'token' => '',
            'preview' => $calendar->getId(),
        ]));
    }

    /** Persönliche Seiten: nicht in Caches, nicht in Suchmaschinen. */
    private function private(Response $response): Response
    {
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
