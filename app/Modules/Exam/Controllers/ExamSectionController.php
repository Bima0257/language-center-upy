<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\AttachSectionQuestionsRequest;
use App\Http\Requests\Exam\ReorderPartsRequest;
use App\Http\Requests\Exam\ReorderQuestionsRequest;
use App\Http\Requests\Exam\SaveArrangementRequest;
use App\Http\Requests\Exam\UpdateSectionRequest;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Modules\Exam\Services\ExamSectionService;
use Illuminate\Http\RedirectResponse;

class ExamSectionController extends Controller
{
    public function __construct(
        private ExamSectionService $sectionService,
    ) {}

    public function update(UpdateSectionRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->update($section, $request->validated());

        return back()->with('success', 'Section diperbarui.');
    }

    public function destroy(Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->delete($section);

        return back()->with('success', 'Section dihapus.');
    }

    public function attachQuestions(AttachSectionQuestionsRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $attached = $this->sectionService->attachQuestions($exam, $section, $request->validated('question_ids'));

        return back()->with('success', "{$attached} soal berhasil ditambahkan ke section.");
    }

    public function saveArrangement(SaveArrangementRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->saveArrangement(
            $section,
            $request->validated('part_order') ?? [],
            $request->validated('question_orders') ?? [],
        );

        return back()->with('success', 'Susunan section disimpan.');
    }

    public function reorderQuestions(ReorderQuestionsRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->reorderQuestions($section, $request->validated('orders'));

        return back()->with('success', 'Nomor soal diperbarui.');
    }

    public function reorderParts(ReorderPartsRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->reorderParts($section, $request->validated('order'));

        return back()->with('success', 'Urutan part diperbarui dan nomor soal disusun ulang.');
    }
}
