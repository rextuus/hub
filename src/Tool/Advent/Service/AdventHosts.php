<?php

declare(strict_types=1);

namespace App\Tool\Advent\Service;

use App\Tool\Advent\Repository\AdventCalendarRepository;
use Symfony\Bundle\FrameworkBundle\Routing\Attribute\AsRoutingConditionService;

/**
 * Routen-Bedingung „gehört dieser Host einem Adventskalender?“ (service('advent_hosts').isCalendarHost(...)).
 * So kann ein Kalender auf eigener Domain laufen, ohne Code zu ändern: Domain im Admin eintragen, DNS + Caddy.
 */
#[AsRoutingConditionService(alias: 'advent_hosts')]
final class AdventHosts
{
    /** @var array<string, bool> */
    private array $cache = [];

    public function __construct(
        private readonly AdventCalendarRepository $calendars,
    ) {
    }

    public function isCalendarHost(string $host): bool
    {
        $host = strtolower($host);

        return $this->cache[$host] ??= null !== $this->calendars->findOneByHost($host);
    }
}
