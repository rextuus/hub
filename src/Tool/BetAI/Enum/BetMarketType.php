<?php

namespace App\Tool\BetAI\Enum;

enum BetMarketType: string
{
    case THREE_WAY = 'THREE_WAY';
    case THREE_WAY_COMBINED = 'THREE_WAY_COMBINED';
    case HANDICAP = 'HANDICAP';
    case WIN_OVER_UNDER = 'WIN_OVER_UNDER';
    case BOTH_TEAMS_SCORE_COMBI = 'BOTH_TEAMS_SCORE_COMBI';
    case UNKNOWN = 'UNKNOWN';

    public function getLabel(): string
    {
        return match($this) {
            self::THREE_WAY => '3-Weg-Wette',
            self::THREE_WAY_COMBINED => '3-Weg-Wette (Kombiniert)',
            self::HANDICAP => 'Handicap',
            self::WIN_OVER_UNDER => 'Sieg & Über/Unter',
            self::BOTH_TEAMS_SCORE_COMBI => 'Beide Teams treffen (Kombi)',
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
            self::BOTH_TEAMS_SCORE_COMBI => 'join_inner',
            self::UNKNOWN => 'help_outline',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::THREE_WAY => 'bg-info-subtle text-info',
            self::THREE_WAY_COMBINED => 'bg-primary-subtle text-primary',
            self::HANDICAP => 'bg-warning-subtle text-warning',
            self::WIN_OVER_UNDER => 'bg-success-subtle text-success',
            self::BOTH_TEAMS_SCORE_COMBI => 'bg-info-subtle text-info',
            self::UNKNOWN => 'bg-secondary-subtle text-secondary',
        };
    }

    public static function fromMarketName(string $marketName): self
    {
        $marketName = mb_strtolower(trim($marketName));
        return match(true) {
            str_contains($marketName, '3-weg-wette (kombiniert)') => self::THREE_WAY_COMBINED,
            str_contains($marketName, '3-weg-wette') => self::THREE_WAY,
            str_contains($marketName, 'handicap') => self::HANDICAP,
            str_contains($marketName, 'sieg &') => self::WIN_OVER_UNDER,
            str_contains($marketName, 'beide teams treffen (kombi)') => self::BOTH_TEAMS_SCORE_COMBI,
            default => self::UNKNOWN,
        };
    }
}
