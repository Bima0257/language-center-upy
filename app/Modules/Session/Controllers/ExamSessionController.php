<?php

namespace App\Modules\Session\Controllers;

use App\Enums\ViolationType;
use App\Http\Controllers\Controller;
use App\Models\ExamSchedule;
use App\Models\ExamSectionQuestion;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Skill;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Security\Actions\LogViolation;
use App\Modules\Session\Actions\CompleteSection;
use App\Modules\Session\Actions\Heartbeat;
use App\Modules\Session\Actions\SaveAnswer;
use App\Modules\Session\Actions\StartExamSession;
use App\Modules\Session\Actions\SubmitExam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        try {
            $result = $this->startExamSession->execute(
                userId: auth()->id(),
                scheduleId: $examSchedule->id,
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('exam.take', ['examSession' => $result->sessionId]);
    }

    public function take(ExamSession $examSession): Response
    {
        $session = $examSession->load([
            'schedule.exam.sections',
            'answers.question',
            'currentSection',
        ]);

        $exam = $session->schedule?->exam;
        $sections = $exam !== null ? $exam->sections : collect();
        $sectionSkillIds = $sections->pluck('skill_id');

        // Load bank questions matching exam section skills, scoped by exam category
        $pivotRows = ExamSectionQuestion::whereIn('exam_section_id', $sections->pluck('id'))->get();
        $pivotQuestionIds = $pivotRows->pluck('question_id');

        $questions = collect();

        if ($pivotRows->isNotEmpty()) {
            $questions = Question::with('passage')
                ->whereIn('id', $pivotQuestionIds)
                ->where('status', 'approved')
                ->get()
                ->each(function ($q) use ($pivotRows) {
                    $row = $pivotRows->firstWhere('question_id', $q->id);
                    $q->order = $row ? $row->order : $q->order;
                })
                ->sortBy('order')
                ->values();
        }

        // Section tanpa susunan soal → fallback semua soal approved by skill
        $emptySections = $sections->filter(fn ($s) => ! $pivotRows->where('exam_section_id', $s->id)->count());

        if ($emptySections->isNotEmpty()) {
            $fallback = Question::with('passage')
                ->whereIn('skill_id', $emptySections->pluck('skill_id'))
                ->where('status', 'approved')
                ->when($exam?->exam_type_id, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $exam->exam_type_id)))
                ->whereNotIn('id', $pivotQuestionIds)
                ->get();

            $questions = $questions->merge($fallback);
        }

        $bankQuestions = $questions;

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
        try {
            $result = $this->submitExam->execute($examSession->id);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

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
            'type' => ['required', Rule::enum(ViolationType::class)],
        ]);

        $result = $this->logViolation->execute(
            sessionId: $examSession->id,
            type: $validated['type'],
        );

        return response()->json([
            'strike' => $result->strikeCount,
            'warning' => ! $result->terminated,
            'sessionActive' => ! $result->terminated,
        ]);
    }
}
