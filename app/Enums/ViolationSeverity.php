<?php

namespace App\Enums;

enum ViolationSeverity: string
{
    case WARNING = 'warning';
    case MINOR = 'minor';
    case MAJOR = 'major';
    case CRITICAL = 'critical';

    public function strikeIncrement(): int
    {
        return match ($this) {
            self::WARNING  => 0,
            self::MINOR    => 1,
            self::MAJOR    => 2,
            self::CRITICAL => 3,
        };
    }
}
