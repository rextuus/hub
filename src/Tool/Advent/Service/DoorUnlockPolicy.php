<?php

declare(strict_types=1);

namespace App\Tool\Advent\Service;

use App\Tool\Advent\Entity\AdventCalendar;
use Psr\Clock\ClockInterface;

/**
 * Wann eine Tür aufgeht – die einzige Stelle, die das entscheidet (Oberfläche und Live-Aktionen fragen hier).
 *
 * Tür n öffnet am Startdatum + (n − 1) Tage um 00:00 in der Zeitzone des Kalenders (Standard Europe/Berlin) und
 * bleibt danach offen – auch nach dem 24., im Januar und in den Folgejahren. Vor dem Start ist keine Tür offen.
 * Gerechnet wird mit dem Kalendertag, nicht mit 24-Stunden-Schritten: Eine Zeitumstellung verschiebt nichts.
 */
final readonly class DoorUnlockPolicy
{
    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function unlocksAt(AdventCalendar $calendar, int $number): \DateTimeImmutable
    {
        self::assertNumber($number);
        $zone = new \DateTimeZone($calendar->getTimezone());

        return new \DateTimeImmutable($calendar->getStartDate()->format('Y-m-d').' 00:00:00', $zone)
            ->modify(\sprintf('+%d days', $number - 1));
    }

    public function isUnlocked(AdventCalendar $calendar, int $number, ?\DateTimeImmutable $now = null): bool
    {
        if ($number < 1 || $number > AdventCalendar::DOORS) {
            return false;
        }

        return ($now ?? $this->clock->now()) >= $this->unlocksAt($calendar, $number);
    }

    /** Anzahl der offenen Türen (0–24). */
    public function unlockedCount(AdventCalendar $calendar, ?\DateTimeImmutable $now = null): int
    {
        $now ??= $this->clock->now();
        $count = 0;
        for ($n = 1; $n <= AdventCalendar::DOORS && $this->isUnlocked($calendar, $n, $now); ++$n) {
            ++$count;
        }

        return $count;
    }

    /** Zeitpunkt der nächsten Tür, oder null, wenn alle offen sind. */
    public function nextUnlock(AdventCalendar $calendar, ?\DateTimeImmutable $now = null): ?\DateTimeImmutable
    {
        $open = $this->unlockedCount($calendar, $now);

        return $open < AdventCalendar::DOORS ? $this->unlocksAt($calendar, $open + 1) : null;
    }

    private static function assertNumber(int $number): void
    {
        if ($number < 1 || $number > AdventCalendar::DOORS) {
            throw new \InvalidArgumentException(\sprintf('Tür %d gibt es nicht (1–%d).', $number, AdventCalendar::DOORS));
        }
    }
}
