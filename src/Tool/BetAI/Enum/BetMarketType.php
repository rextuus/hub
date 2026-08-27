<?php

namespace App\Tool\BetAI\Enum;

enum BetMarketType: string
{
    case THREE_WAY = 'THREE_WAY';
    case THREE_WAY_COMBINED = 'THREE_WAY_COMBINED';
    case HANDICAP = 'HANDICAP';
    case WIN_OVER_UNDER = 'WIN_OVER_UNDER';
    case UNKNOWN = 'UNKNOWN';

    public function getLabel(): string
    {
        return match($this) {
            self::THREE_WAY => '3-Weg-Wette',
            self::THREE_WAY_COMBINED => '3-Weg-Wette (Kombiniert)',
            self::HANDICAP => 'Handicap',
            self::WIN_OVER_UNDER => 'Sieg & Über/Unter',
            self::UNKNOWN => 'Unbekannt',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::THREE_WAY => 'compare_arrows',
            self::THREE_WAY_COMBINED => 'layers',
            self::HANDICAP => 'exposure_plus_1',
            self::WIN_OVER_UNDER => 'add_task',
            self::UNKNOWN => 'help_outline',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::THREE_WAY => 'bg-info/10 text-info',
            self::THREE_WAY_COMBINED => 'bg-tertiary/10 text-tertiary',
            self::HANDICAP => 'bg-warning/10 text-warning',
            self::WIN_OVER_UNDER => 'bg-success/10 text-success',
            self::UNKNOWN => 'bg-secondary/10 text-secondary',
        };
    }

    public static function fromMarketName(string $marketName): self
    {
        return match(true) {
            str_contains($marketName, '3-Weg-Wette (Kombiniert)') => self::THREE_WAY_COMBINED,
            str_contains($marketName, '3-Weg-Wette') => self::THREE_WAY,
            str_contains($marketName, 'Handicap') => self::HANDICAP,
            str_contains($marketName, 'Sieg &') => self::WIN_OVER_UNDER,
            default => self::UNKNOWN,
        };
    }
}
