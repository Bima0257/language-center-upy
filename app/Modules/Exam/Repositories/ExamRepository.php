<?php

namespace App\Modules\Exam\Repositories;

use App\Models\Exam;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ExamRepository implements ExamRepositoryInterface
{
    public function paginateWithSections(int $perPage = 15): LengthAwarePaginator
    {
        return Exam::withCount('sections')->paginate($perPage);
    }

    public function findWithRelations(int $id): ?Exam
    {
        return Exam::with('sections.questionGroups.questions')->find($id);
    }

    public function create(array $data): Exam
    {
        return Exam::create($data);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);
        return $exam->fresh();
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    public function all(): Collection
    {
        return Exam::with('sections')->get();
    }
}
