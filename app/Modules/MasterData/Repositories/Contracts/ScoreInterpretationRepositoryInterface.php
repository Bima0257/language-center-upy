<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\ScoreInterpretation;
use Illuminate\Database\Eloquent\Collection;

interface ScoreInterpretationRepositoryInterface
{
    public function allWithExamType(): Collection;

    public function create(array $data): ScoreInterpretation;

    public function update(ScoreInterpretation $interpretation, array $data): ScoreInterpretation;

    public function delete(ScoreInterpretation $interpretation): void;
}
