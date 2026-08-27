<?php

namespace App\Tool\BetAI\Enum;

enum BetMarketType: string
{
    case THREE_WAY = 'THREE_WAY';
    case THREE_WAY_COMBINED = 'THREE_WAY_COMBINED';
    case HANDICAP = 'HANDICAP';
    case WIN_OVER_UNDER = 'WIN_OVER_UNDER';
    case BOTH_TEAMS_SCORE_COMBI = 'BOTH_TEAMS_SCORE_COMBI';
    case OVER_UNDER = 'OVER_UNDER';
    case UNKNOWN = 'UNKNOWN';

    public function getLabel(): string
    {
        return match($this) {
            self::THREE_WAY => '1X2 / 3-Weg',
            self::THREE_WAY_COMBINED => '3-Weg-Wette (Kombiniert)',
            self::HANDICAP => 'Handicap',
            self::WIN_OVER_UNDER => 'Sieg & Über/Unter',
            self::BOTH_TEAMS_SCORE_COMBI => 'Beide Teams treffen (Kombi)',
            self::OVER_UNDER => 'Über/Unter',
            self::UNKNOWN => 'Unbekannt',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::THREE_WAY => 'swap_horiz',
            self::THREE_WAY_COMBINED => 'layers',
            self::HANDICAP => 'exposure_plus_1',
            self::WIN_OVER_UNDER => 'add_task',
            self::BOTH_TEAMS_SCORE_COMBI => 'join_inner',
            self::OVER_UNDER => 'unfold_more',
            self::UNKNOWN => 'help_outline',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::THREE_WAY => 'bg-info/20 text-info',
            self::THREE_WAY_COMBINED => 'bg-primary/20 text-primary',
            self::HANDICAP => 'bg-warning/20 text-warning',
            self::WIN_OVER_UNDER => 'bg-success/20 text-success',
            self::BOTH_TEAMS_SCORE_COMBI => 'bg-info/20 text-info',
            self::OVER_UNDER => 'bg-secondary/20 text-secondary',
            self::UNKNOWN => 'bg-neutral/20 text-neutral',
        };
    }

    public static function fromMarketName(string $marketName): self
    {
        $marketName = mb_strtolower(trim($marketName));
        return match(true) {
            str_contains($marketName, '3-weg-wette (kombiniert)') => self::THREE_WAY_COMBINED,
            str_contains($marketName, '3-weg-wette') || $marketName === '1x2' => self::THREE_WAY,
            str_contains($marketName, 'handicap') => self::HANDICAP,
            str_contains($marketName, 'sieg &') => self::WIN_OVER_UNDER,
            str_contains($marketName, 'beide teams treffen (kombi)') => self::BOTH_TEAMS_SCORE_COMBI,
            str_contains($marketName, 'over/under') || str_contains($marketName, 'über/unter') => self::OVER_UNDER,
            default => self::UNKNOWN,
        };
    }
}
