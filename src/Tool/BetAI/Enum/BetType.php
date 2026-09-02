<?php

namespace App\Tool\BetAI\Enum;

enum BetType: string
{
    case SINGLE = 'SINGLE';
    case COMBI = 'COMBI';
    case SYSTEM = 'SYSTEM';

    public function getIcon(): string
    {
        return match($this) {
            self::SINGLE => 'person',
            self::COMBI => 'view_in_ar',
            self::SYSTEM => 'account_tree',
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::SINGLE => 'Einzelwette',
            self::COMBI => 'Kombiwette',
            self::SYSTEM => 'Systemwette',
        };
    }

    public function getContainerClass(): string
    {
        return 'bg-surface-container-high';
    }

    public function getTextColorClass(): string
    {
        return match($this) {
            self::SINGLE => 'text-info',
            self::COMBI => 'text-tertiary',
            self::SYSTEM => 'text-warning',
        };
    }
}
