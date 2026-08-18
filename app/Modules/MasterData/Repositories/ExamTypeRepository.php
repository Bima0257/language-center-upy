<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\ExamType;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ExamTypeRepository implements ExamTypeRepositoryInterface
{
    public function allWithQuestionBankCounts(): Collection
    {
        return ExamType::withCount(['questionBanks', 'exams'])->orderBy('name')->get();
    }

    public function allOrdered(): Collection
    {
        return ExamType::orderBy('name')->get();
    }

    public function allActiveOrdered(): Collection
    {
        return ExamType::where('is_active', true)->orderBy('name')->get();
    }

    public function create(array $data): ExamType
    {
        return ExamType::create($data);
    }

    public function update(ExamType $examType, array $data): ExamType
    {
        $examType->update($data);

        return $examType->fresh();
    }

    public function delete(ExamType $examType): void
    {
        $examType->delete();
    }
}
