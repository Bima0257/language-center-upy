<?php

namespace App\Enums;

use App\Models\Skill;
use Illuminate\Support\Facades\Cache;

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
     * Dapatkan skill ID dari database berdasarkan code.
     */
    public function skillId(): int
    {
        return Cache::remember("skill_id_{$this->value}", 3600, fn () => Skill::where('code', $this->value)->value('id'));
    }

    /**
     * Opsi statis untuk dropdown frontend (legacy — gunakan Skill::all() untuk data dinamis).
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

    /**
     * Konversi array skill codes ke array skill IDs.
     *
     * @param  array<string>  $codes
     * @return array<int>
     */
    public static function toSkillIds(array $codes): array
    {
        return array_map(
            static fn (string $code) => self::from($code)->skillId(),
            $codes,
        );
    }
}
