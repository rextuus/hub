<?php

declare(strict_types=1);

namespace App\Tool\Advent\Service;

use App\Tool\Advent\Entity\AdventParticipant;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Persönlicher Link eines Teilnehmers. Mit eigener Domain des Kalenders: https://<domain>/advent/<token>,
 * sonst auf der Domain, über die gerade gefragt wird (bzw. DEFAULT_URI in Konsolenbefehlen).
 */
final readonly class AdventLinks
{
    public function __construct(
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function participantUrl(AdventParticipant $participant): string
    {
        $path = $this->urls->generate('app_advent_calendar', ['token' => $participant->getToken()]);
        $host = $participant->getCalendar()?->getHost();

        return null !== $host
            ? 'https://'.$host.$path
            : $this->urls->generate('app_advent_calendar', ['token' => $participant->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
