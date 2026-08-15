<?php

namespace App\Modules\Schedule\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\StoreSlotRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Http\Requests\Schedule\UpdateSlotRequest;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamScheduleSlot;
use App\Modules\Schedule\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleService $scheduleService,
    ) {}

    public function all(): Response
    {
        return Inertia::render('Admin/Schedules/All', $this->scheduleService->allData());
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

        return to_route('admin.schedules.all')
            ->with('success', 'Periode ujian berhasil dibuat.');
    }

    public function show(ExamSchedule $schedule): Response
    {
        return Inertia::render('Admin/Schedules/Show', $this->scheduleService->showData($schedule));
    }

    public function edit(ExamSchedule $schedule): Response
    {
        return Inertia::render('Admin/Schedules/Edit', [
            'schedule' => $this->scheduleService->showData($schedule)['schedule'],
        ]);
    }

    public function update(UpdateScheduleRequest $request, ExamSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->update($schedule, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Periode ujian diperbarui.');
    }

    public function destroy(ExamSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->delete($schedule);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return to_route('admin.schedules.all')
            ->with('success', 'Periode ujian dihapus.');
    }

    public function storeSlot(StoreSlotRequest $request, ExamSchedule $schedule): RedirectResponse
    {
        try {
            $this->scheduleService->createSlot($schedule, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sesi berhasil ditambahkan.');
    }

    public function updateSlot(UpdateSlotRequest $request, ExamScheduleSlot $slot): RedirectResponse
    {
        try {
            $this->scheduleService->updateSlot($slot, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sesi diperbarui.');
    }

    public function destroySlot(ExamScheduleSlot $slot): RedirectResponse
    {
        try {
            $this->scheduleService->deleteSlot($slot);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sesi dihapus.');
    }
}
