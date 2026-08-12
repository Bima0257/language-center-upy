<?php

namespace App\Modules\Schedule\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Modules\Schedule\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

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

    public function all(): Response
    {
        return Inertia::render('Admin/Schedules/All', [
            'schedules' => $this->scheduleService->paginatedAll(),
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
        try {
            $this->scheduleService->create(array_merge($request->validated(), ['exam_id' => $exam->id]));
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.schedules.index', $exam)
            ->with('success', 'Jadwal berhasil dibuat.');
    }

    public function edit(Exam $exam, ExamSchedule $schedule): Response
    {
        return Inertia::render('Admin/Schedules/Edit', [
            'exam' => $exam,
            'schedule' => $schedule,
        ]);
    }

    public function update(UpdateScheduleRequest $request, Exam $exam, ExamSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->update($schedule, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Jadwal diperbarui.');
    }

    public function destroy(Exam $exam, ExamSchedule $schedule): RedirectResponse
    {
        $this->scheduleService->delete($schedule);

        return back()->with('success', 'Jadwal dihapus.');
    }
}
