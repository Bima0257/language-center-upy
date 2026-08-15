<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\ScoreInterpretation;
use App\Modules\MasterData\Repositories\Contracts\ScoreInterpretationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ScoreInterpretationRepository implements ScoreInterpretationRepositoryInterface
{
    public function allWithExamType(): Collection
    {
        return ScoreInterpretation::with('examType')->orderBy('min_score')->get();
    }

    public function create(array $data): ScoreInterpretation
    {
        return ScoreInterpretation::create($data);
    }

    public function update(ScoreInterpretation $interpretation, array $data): ScoreInterpretation
    {
        $interpretation->update($data);

        return $interpretation->fresh();
    }

    public function delete(ScoreInterpretation $interpretation): void
    {
        $interpretation->delete();
    }
}
