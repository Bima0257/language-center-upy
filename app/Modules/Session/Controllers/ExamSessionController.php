<?php

namespace App\Modules\Session\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Skill;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Session\Actions\CompleteSection;
use App\Modules\Session\Actions\Heartbeat;
use App\Modules\Session\Actions\SaveAnswer;
use App\Modules\Session\Actions\StartExamSession;
use App\Modules\Session\Actions\SubmitExam;
use App\Modules\Security\Actions\LogViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamSessionController extends Controller
{
    public function __construct(
        private StartExamSession $startExamSession,
        private SaveAnswer $saveAnswer,
        private CompleteSection $completeSection,
        private SubmitExam $submitExam,
        private Heartbeat $heartbeat,
        private LogViolation $logViolation,
        private ScheduleRepositoryInterface $scheduleRepo,
    ) {}

    public function available(): Response
    {
        return Inertia::render('Exam/Available', [
            'schedules' => $this->scheduleRepo->getAvailableSchedules(),
        ]);
    }

    public function preCheck(ExamSchedule $examSchedule): Response
    {
        return Inertia::render('Exam/PreCheck', [
            'schedule' => $examSchedule->load('exam'),
        ]);
    }

    public function start(ExamSchedule $examSchedule): RedirectResponse
    {
        $result = $this->startExamSession->execute(
            userId: auth()->id(),
            scheduleId: $examSchedule->id,
        );

        return to_route('exam.take', ['examSession' => $result->sessionId]);
    }

    public function take(ExamSession $examSession): Response
    {
        $session = $examSession->load([
            'schedule.exam.sections',
            'answers.question',
            'currentSection',
        ]);

        // Load bank questions matching exam section skills
        $sectionSkillIds = $session->schedule?->exam?->sections?->pluck('skill_id') ?? collect();
        $bankQuestions = Question::with('passage')
            ->whereIn('skill_id', $sectionSkillIds)
            ->where('status', 'approved')
            ->get();

        return Inertia::render('Exam/Take', [
            'session' => $session,
            'bankQuestions' => $bankQuestions,
            'skills' => Skill::whereIn('id', $sectionSkillIds)->pluck('name', 'id'),
        ]);
    }

    public function saveAnswer(Request $request, ExamSession $examSession): JsonResponse
    {
        $validated = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'answer_text' => ['nullable', 'string'],
        ]);

        $this->saveAnswer->execute(
            sessionId: $examSession->id,
            questionId: $validated['question_id'],
            answer: $validated['answer_text'] ?? null,
        );

        return response()->json(['status' => 'saved', 'timestamp' => now()->toIso8601String()]);
    }

    public function completeSection(Request $request, ExamSession $examSession): JsonResponse
    {
        $this->completeSection->execute($examSession);

        return response()->json(['status' => 'section_completed']);
    }

    public function submit(ExamSession $examSession): RedirectResponse
    {
        $result = $this->submitExam->execute($examSession->id);

        return to_route('dashboard')
            ->with('success', "Ujian berhasil disubmit. Skor Anda: Reading {$result->scoreReading}/30, Listening {$result->scoreListening}/30");
    }

    public function heartbeat(ExamSession $examSession): JsonResponse
    {
        $this->heartbeat->execute($examSession->id);

        return response()->json(['status' => 'ok']);
    }

    public function logViolation(Request $request, ExamSession $examSession): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->logViolation->execute(
            sessionId: $examSession->id,
            type: $validated['type'],
        );

        return response()->json([
            'strike' => $result->strikeCount,
            'warning' => !$result->terminated,
            'sessionActive' => !$result->terminated,
        ]);
    }
}
