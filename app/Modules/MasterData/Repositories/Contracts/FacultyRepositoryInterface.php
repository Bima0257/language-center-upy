<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Collection;

interface FacultyRepositoryInterface
{
    public function allWithDepartmentCounts(): Collection;

    public function allActiveOrdered(): Collection;

    public function create(array $data): Faculty;

    public function update(Faculty $faculty, array $data): Faculty;

    public function delete(Faculty $faculty): void;
}
