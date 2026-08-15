<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\ExamType;
use Illuminate\Database\Eloquent\Collection;

interface ExamTypeRepositoryInterface
{
    public function allWithQuestionBankCounts(): Collection;

    public function allOrdered(): Collection;

    public function allActiveOrdered(): Collection;

    public function create(array $data): ExamType;

    public function update(ExamType $examType, array $data): ExamType;

    public function delete(ExamType $examType): void;
}
