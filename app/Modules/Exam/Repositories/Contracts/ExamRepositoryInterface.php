<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\Exam;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ExamRepositoryInterface
{
    public function paginateWithSections(int $perPage = 15): LengthAwarePaginator;
    public function findWithRelations(int $id): ?Exam;
    public function create(array $data): Exam;
    public function update(Exam $exam, array $data): Exam;
    public function delete(Exam $exam): void;
    public function all(): Collection;
}
