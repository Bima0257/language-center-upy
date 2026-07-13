<?php

namespace App\Modules\Schedule\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Modules\Schedule\Services\ScheduleService;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService,
    ) {}

    public function index(Exam $exam): Response
    {
        return Inertia::render('Admin/Schedules/Index', [
            'exam' => $exam,
            'schedules' => $this->scheduleService->paginatedByExam($exam->id),
        ]);
    }

    public function create(Exam $exam): Response
    {
        return Inertia::render('Admin/Schedules/Create', [
            'exam' => $exam,
        ]);
    }

    public function store(StoreScheduleRequest $request, Exam $exam): RedirectResponse
    {
        $this->scheduleService->create(array_merge($request->validated(), ['exam_id' => $exam->id]));

        return to_route('admin.schedules.index', $exam)
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    public function destroy(Exam $exam, ExamSchedule $schedule): RedirectResponse
    {
        $this->scheduleService->delete($schedule);

        return back()->with('success', 'Jadwal dihapus.');
    }
}
