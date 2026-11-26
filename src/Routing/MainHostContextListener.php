<?php

declare(strict_types=1);

namespace App\Routing;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;

/**
 * Routen der Hauptdomain (Startseite) haben den Host-Platzhalter {main_host}. Kommt die Anfrage über eine der
 * Hauptdomains (oder lokal über localhost), erzeugt Symfony damit relative Links auf denselben Host; von der Domain
 * eines Tools aus (später z. B. kresh.wh-company.com) zeigt der Link absolut auf app.main_host.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 40)]
final readonly class MainHostContextListener
{
    public const string PARAMETER = 'main_host';

    public function __construct(
        private RequestContext $context,
        #[Autowire('%app.main_host%')]
        private string $mainHost,
        #[Autowire('%app.main_host_pattern%')]
        private string $mainHostPattern,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $host = $event->getRequest()->getHost();
        $this->context->setParameter(
            self::PARAMETER,
            preg_match('#^(?:'.$this->mainHostPattern.')$#i', $host) ? $host : $this->mainHost,
        );
    }
}
