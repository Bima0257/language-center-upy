<?php

namespace App\Modules\Scoring\Services;

use App\Models\ScoringRule;

class ScoreConversionService
{
    public function convert(?int $examTypeId, string $skill, int $rawScore, int $maxRaw): float
    {
        if (! $examTypeId) {
            return 0;
        }

        $rule = ScoringRule::where('exam_type_id', $examTypeId)
            ->where('section_skill', $skill)
            ->first();

        if (! $rule || $maxRaw <= 0) {
            return 0;
        }

        $conversionTable = $rule->conversion_table;

        $pct = round($rawScore / $maxRaw * $rule->max_raw);
        $pct = min($pct, $rule->max_raw);

        return (float) ($conversionTable[(string) $pct] ?? 0);
    }

    public function rawToScaledReading(int $raw, int $maxRaw): float
    {
        return $this->convert(1, 'reading', $raw, $maxRaw);
    }

    public function rawToScaledListening(int $raw, int $maxRaw): float
    {
        return $this->convert(1, 'listening', $raw, $maxRaw);
    }

    public function speakingAverageToScaled(float $average): float
    {
        return round($average / 4 * 30, 1);
    }

    public function writingAverageToScaled(float $average): float
    {
        return round($average / 5 * 30, 1);
    }
}
