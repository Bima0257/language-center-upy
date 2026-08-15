<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use App\Models\Exam;
use App\Modules\Exam\Services\ExamService;
use App\Modules\MasterData\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ExamController extends Controller
{
    public function __construct(
        private ExamService $examService,
        private MasterDataService $masterData,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Exams/Index', [
            'exams' => $this->examService->paginated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Exams/Create', $this->examService->createData() + [
            'examTypes' => $this->masterData->activeExamTypes(),
        ]);
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = $this->examService->createWithSections($request->validated());

        return to_route('admin.exams.show', $exam)
            ->with('success', "Ujian berhasil dibuat. {$exam->sections->count()} section otomatis dibuat.");
    }

    public function show(Exam $exam): Response
    {
        return Inertia::render('Admin/Exams/Show', $this->examService->showData($exam));
    }

    public function edit(Exam $exam): Response
    {
        return Inertia::render('Admin/Exams/Edit', $this->examService->editData($exam) + [
            'examTypes' => $this->masterData->activeExamTypes(),
        ]);
    }

    public function update(UpdateExamRequest $request, Exam $exam): RedirectResponse
    {
        $this->examService->update($exam, $request->validated());

        return back()->with('success', 'Ujian diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->examService->delete($exam);

        return to_route('admin.exams.index')
            ->with('success', 'Ujian dihapus.');
    }
}
