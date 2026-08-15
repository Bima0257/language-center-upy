<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\Passage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PassageRepositoryInterface
{
    public function paginateWithCounts(int $perPage = 20): LengthAwarePaginator;

    public function allOrdered(): Collection;

    public function find(int $id): ?Passage;

    public function create(array $data): Passage;

    public function update(Passage $passage, array $data): Passage;

    public function delete(Passage $passage): void;

    public function hasQuestions(int $passageId): bool;

    public function hasListeningQuestions(int $passageId): bool;

    public function count(): int;
}
