<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function allWithFaculty(): Collection;

    public function allActiveOrdered(): Collection;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;

    public function delete(Department $department): void;
}
