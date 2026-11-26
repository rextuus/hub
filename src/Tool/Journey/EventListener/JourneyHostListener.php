<?php

declare(strict_types=1);

namespace App\Tool\Journey\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Die Domain der Journey (app.journey_host) gehört allein der Journey: Die übrigen Routen von hub hängen an keiner
 * Domain (Admin, Login, Fotokalender, …) und liefern dort 404 – bevor die Firewall läuft, also ohne Sitzung und
 * ohne Login-Weiterleitung. Erlaubt sind nur die Journey-Routen, der Healthcheck und lokal (ohne imgproxy) die
 * Originale aus journey/. Jede Antwort auf dieser Domain bekommt X-Robots-Tag: Die Seite ist privat.
 */
final readonly class JourneyHostListener
{
    private const array ALLOWED_ROUTES = ['app_health'];

    public function __construct(
        #[Autowire('%app.journey_host%')]
        private string $journeyHost,
    ) {
    }

    /** Nach dem Router (32), vor der Firewall (8). */
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 31)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->isJourneyHost($request->getHost())) {
            return;
        }

        $route = (string) $request->attributes->get('_route');
        $allowed = str_starts_with($route, 'app_journey')
            || \in_array($route, self::ALLOWED_ROUTES, true)
            || str_starts_with($route, '_wdt') || str_starts_with($route, '_profiler') // gibt es nur in dev
            || ('app_media_original' === $route && str_starts_with((string) $request->attributes->get('path'), 'journey/'));
        if (!$allowed) {
            throw new NotFoundHttpException();
        }
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function onResponse(ResponseEvent $event): void
    {
        if ($this->isJourneyHost($event->getRequest()->getHost())) {
            $event->getResponse()->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    private function isJourneyHost(string $host): bool
    {
        return 0 === strcasecmp($host, $this->journeyHost);
    }
}
