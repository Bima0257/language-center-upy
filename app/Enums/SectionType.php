<?php

namespace App\Enums;

enum SectionType: string
{
    case READING = 'reading';
    case LISTENING = 'listening';
    case SPEAKING = 'speaking';
    case WRITING = 'writing';

    /**
     * @deprecated Gunakan field `skill` (string) di exam_sections.
     *   Enum ini dipertahankan untuk backward compatibility.
     */
    public function label(): string
    {
        return match ($this) {
            self::READING   => 'Reading',
            self::LISTENING => 'Listening',
            self::SPEAKING  => 'Speaking',
            self::WRITING   => 'Writing',
        };
    }
}
