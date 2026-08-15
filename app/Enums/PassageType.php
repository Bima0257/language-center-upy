<?php

namespace App\Enums;

enum PassageType: string
{
    case TEXT = 'text';
    case AUDIO = 'audio';
    case IMAGE = 'image';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Text',
            self::AUDIO => 'Audio',
            self::IMAGE => 'Image',
        };
    }
}
