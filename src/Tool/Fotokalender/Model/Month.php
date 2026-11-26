<?php

declare(strict_types=1);

namespace App\Tool\Fotokalender\Model;

/**
 * Deutsche Monatsnamen und URL-Kürzel (/kalender/2022/maerz) sowie die englischen Namen der alten Seite
 * (wh-company.de/calendar/detail/march.html) für die Weiterleitungen.
 */
final class Month
{
    public const array NAMES = [
        1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
        7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
    ];

    public const array SLUGS = [
        1 => 'januar', 2 => 'februar', 3 => 'maerz', 4 => 'april', 5 => 'mai', 6 => 'juni',
        7 => 'juli', 8 => 'august', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'dezember',
    ];

    public const array LEGACY_SLUGS = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4, 'may' => 5, 'june' => 6,
        'july' => 7, 'august' => 8, 'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
    ];

    public static function name(int $month): string
    {
        return self::NAMES[$month] ?? throw new \InvalidArgumentException('Monat 1–12 erwartet.');
    }

    public static function slug(int $month): string
    {
        return self::SLUGS[$month] ?? throw new \InvalidArgumentException('Monat 1–12 erwartet.');
    }

    public static function fromSlug(string $slug): ?int
    {
        $month = array_search(strtolower($slug), self::SLUGS, true);

        return false === $month ? null : $month;
    }

    /** @return array<string, int> Auswahl für EasyAdmin */
    public static function choices(): array
    {
        return array_flip(self::NAMES);
    }
}
