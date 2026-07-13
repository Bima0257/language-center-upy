<?php

namespace App\Modules\Exam\Services;

use App\Models\Exam;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamService
{
    public function __construct(
        private ExamRepositoryInterface $examRepo,
    ) {}

    public function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->examRepo->paginateWithSections($perPage);
    }

    public function findWithRelations(int $id): ?Exam
    {
        return $this->examRepo->findWithRelations($id);
    }

    public function create(array $data): Exam
    {
        return $this->examRepo->create($data);
    }

    public function update(Exam $exam, array $data): Exam
    {
        return $this->examRepo->update($exam, $data);
    }

    public function delete(Exam $exam): void
    {
        $this->examRepo->delete($exam);
    }
}
