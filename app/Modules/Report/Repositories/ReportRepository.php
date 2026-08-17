<?php

namespace App\Modules\Report\Repositories;

use App\Models\ExamSession;
use App\Modules\Report\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportRepository implements ReportRepositoryInterface
{
    public function paginateIntegrity(int $perPage = 20): LengthAwarePaginator
    {
        return ExamSession::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findForExport(int $id): ?ExamSession
    {
        return ExamSession::with([
            'user', 'violationLogs', 'slot.schedule.exam', 'slot', 'answers.question',
        ])->find($id);
    }
}
