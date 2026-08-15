<?php

namespace App\Modules\Exam\Repositories;

use App\Models\QuestionBank;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class QuestionBankRepository implements QuestionBankRepositoryInterface
{
    public function allWithExamTypeAndCounts(): Collection
    {
        return QuestionBank::with('examType')->withCount('questions')->orderBy('name')->get();
    }

    public function allActiveOrdered(): Collection
    {
        return QuestionBank::where('is_active', true)->orderBy('name')->get();
    }

    public function allActiveWithExamTypeOrdered(): Collection
    {
        return QuestionBank::with('examType')->where('is_active', true)->orderBy('name')->get();
    }

    public function existsActive(int $id): bool
    {
        return QuestionBank::whereKey($id)->where('is_active', true)->exists();
    }

    public function create(array $data): QuestionBank
    {
        return QuestionBank::create($data);
    }

    public function update(QuestionBank $questionBank, array $data): QuestionBank
    {
        $questionBank->update($data);

        return $questionBank->fresh();
    }

    public function delete(QuestionBank $questionBank): void
    {
        $questionBank->delete();
    }
}
