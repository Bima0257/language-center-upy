<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Collection;

interface QuestionBankRepositoryInterface
{
    public function allWithExamTypeAndCounts(): Collection;

    public function allActiveOrdered(): Collection;

    public function allActiveWithExamTypeOrdered(): Collection;

    public function existsActive(int $id): bool;

    public function create(array $data): QuestionBank;

    public function update(QuestionBank $questionBank, array $data): QuestionBank;

    public function delete(QuestionBank $questionBank): void;
}
