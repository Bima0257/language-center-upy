<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function integrity(): Response
    {
        $sessions = ExamSession::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Reports/SessionReport', [
            'sessions' => $sessions,
        ]);
    }

    public function exportIntegrity(ExamSession $examSession): Response
    {
        $session = $examSession->load([
            'user', 'violationLogs', 'schedule.exam', 'answers.question',
        ]);

        return Inertia::render('Admin/Reports/IntegrityExport', [
            'session' => $session,
        ]);
    }
}
