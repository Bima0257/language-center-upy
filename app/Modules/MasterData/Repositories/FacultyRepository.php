<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\Faculty;
use App\Modules\MasterData\Repositories\Contracts\FacultyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class FacultyRepository implements FacultyRepositoryInterface
{
    public function allWithDepartmentCounts(): Collection
    {
        return Faculty::withCount('departments')->orderBy('name')->get();
    }

    public function allActiveOrdered(): Collection
    {
        return Faculty::where('is_active', true)->orderBy('name')->get();
    }

    public function create(array $data): Faculty
    {
        return Faculty::create($data);
    }

    public function update(Faculty $faculty, array $data): Faculty
    {
        $faculty->update($data);

        return $faculty->fresh();
    }

    public function delete(Faculty $faculty): void
    {
        $faculty->delete();
    }
}
