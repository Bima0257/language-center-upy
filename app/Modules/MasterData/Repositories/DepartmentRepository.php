<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\Department;
use App\Modules\MasterData\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function allWithFaculty(): Collection
    {
        return Department::with('faculty')->orderBy('name')->get();
    }

    public function allActiveOrdered(): Collection
    {
        return Department::where('is_active', true)->orderBy('name')->get();
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update($data);

        return $department->fresh();
    }

    public function delete(Department $department): void
    {
        $department->delete();
    }
}
