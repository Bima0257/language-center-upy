<?php

namespace App\Modules\Report\Repositories\Contracts;

use App\Models\ExamSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    public function paginateIntegrity(int $perPage = 20): LengthAwarePaginator;

    public function findForExport(int $id): ?ExamSession;
}
