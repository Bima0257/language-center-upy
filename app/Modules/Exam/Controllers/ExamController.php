<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Modules\Exam\Services\ExamService;
use App\Http\Requests\Exam\StoreExamRequest;
use App\Http\Requests\Exam\UpdateExamRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ExamController extends Controller
{
    public function __construct(
        private ExamService $examService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Exams/Index', [
            'exams' => $this->examService->paginated(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Exams/Create');
    }

    public function store(StoreExamRequest $request): RedirectResponse
    {
        $exam = $this->examService->create($request->validated());

        return to_route('admin.exams.show', $exam)
            ->with('success', 'Ujian berhasil dibuat.');
    }

    public function show(Exam $exam): Response
    {
        return Inertia::render('Admin/Exams/Show', [
            'exam' => $this->examService->findWithRelations($exam->id),
        ]);
    }

    public function edit(Exam $exam): Response
    {
        return Inertia::render('Admin/Exams/Edit', [
            'exam' => $this->examService->findWithRelations($exam->id),
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
