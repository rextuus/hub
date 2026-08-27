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
            self::COMBI => 'group',
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
}
