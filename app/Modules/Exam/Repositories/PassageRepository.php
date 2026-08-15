<?php

namespace App\Modules\Exam\Repositories;

use App\Models\Passage;
use App\Modules\Exam\Repositories\Contracts\PassageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PassageRepository implements PassageRepositoryInterface
{
    public function paginateWithCounts(int $perPage = 20): LengthAwarePaginator
    {
        return Passage::withCount('questions')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function allOrdered(): Collection
    {
        return Passage::orderBy('title')->get();
    }

    public function find(int $id): ?Passage
    {
        return Passage::find($id);
    }

    public function create(array $data): Passage
    {
        return Passage::create($data);
    }

    public function update(Passage $passage, array $data): Passage
    {
        $passage->update($data);

        return $passage->fresh();
    }

    public function delete(Passage $passage): void
    {
        $passage->delete();
    }

    public function hasQuestions(int $passageId): bool
    {
        return Passage::whereKey($passageId)->whereHas('questions')->exists();
    }

    public function hasListeningQuestions(int $passageId): bool
    {
        return Passage::whereKey($passageId)
            ->whereHas('questions', fn ($q) => $q->where('skill', 'listening'))
            ->exists();
    }

    public function count(): int
    {
        return Passage::count();
    }
}
