<?php

namespace App\Modules\Proctor\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Modules\Proctor\Actions\ReviewSession;
use App\Modules\Proctor\DTOs\ReviewDecisionData;
use App\Modules\Proctor\Services\ProctorService;
use App\Modules\Security\Actions\TerminateSession;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProctorController extends Controller
{
    public function __construct(
        private ProctorService $proctorService,
        private ReviewSession $reviewSession,
        private TerminateSession $terminateSession,
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('Proctor/Dashboard', [
            'activeSessions' => $this->proctorService->getActiveSessions(),
            'flaggedSessions' => $this->proctorService->getFlaggedSessions(),
        ]);
    }

    public function showSession(ExamSession $examSession): Response
    {
        $session = $this->sessionRepo->findWithAnswers($examSession->id);

        return Inertia::render('Proctor/SessionReview', [
            'session' => $session->loadMissing(['user', 'violationLogs', 'schedule.exam', 'answers.question']),
        ]);
    }

    public function violations(ExamSession $examSession): Response
    {
        return Inertia::render('Proctor/SessionReview', [
            'session' => $examSession->load(['user', 'violationLogs']),
        ]);
    }

    public function terminate(Request $request, ExamSession $examSession): RedirectResponse
    {
        $this->terminateSession->execute(
            $examSession->id,
            $request->input('reason', 'Dihentikan oleh proctor.'),
        );

        return back()->with('success', 'Sesi ujian dihentikan.');
    }

    public function review(Request $request, ExamSession $examSession): RedirectResponse
    {
        $validated = $request->validate([
            'review_status' => ['required', 'in:sah,ujian_ulang,dibatalkan'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->reviewSession->execute($examSession->id, new ReviewDecisionData(
            status: $validated['review_status'],
            note: $validated['review_note'] ?? null,
            reviewerId: auth()->id(),
        ));

        return back()->with('success', 'Keputusan review disimpan.');
    }

    public function flaggedSessions(): Response
    {
        return Inertia::render('Proctor/Dashboard', [
            'activeSessions' => collect(),
            'flaggedSessions' => $this->proctorService->getFlaggedSessions(),
        ]);
    }
}
