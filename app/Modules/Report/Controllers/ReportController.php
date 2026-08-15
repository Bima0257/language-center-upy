<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Modules\Report\Repositories\Contracts\ReportRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private ReportRepositoryInterface $reports,
    ) {}

    public function integrity(): Response
    {
        return Inertia::render('Admin/Reports/SessionReport', [
            'sessions' => $this->reports->paginateIntegrity(),
        ]);
    }

    public function exportIntegrity(ExamSession $examSession): Response
    {
        return Inertia::render('Admin/Reports/IntegrityExport', [
            'session' => $this->reports->findForExport($examSession->id),
        ]);
    }
}
