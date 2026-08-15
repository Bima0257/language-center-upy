<?php

namespace App\Enums;

enum SkillCode: string
{
    case READING = 'reading';
    case LISTENING = 'listening';

    public function label(): string
    {
        return match ($this) {
            self::READING => 'Reading',
            self::LISTENING => 'Listening',
        };
    }

    /**
     * Opsi statis untuk dropdown frontend.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $skill) => ['value' => $skill->value, 'label' => $skill->label()],
            self::cases(),
        );
    }

    /**
     * Map kode -> label untuk lookup cepat.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_column(self::options(), 'label', 'value');
    }
}
