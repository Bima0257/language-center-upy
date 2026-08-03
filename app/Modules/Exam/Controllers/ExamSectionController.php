<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Modules\Exam\Services\ExamSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamSectionController extends Controller
{
    public function __construct(
        private ExamSectionService $sectionService,
    ) {}

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'skill_id' => ['required', 'exists:skills,id'],
            'title' => ['required', 'string', 'max:255'],
            'order' => ['required', 'integer'],
            'duration_minutes' => ['nullable', 'integer'],
            'instructions' => ['nullable', 'string'],
            'total_questions' => ['nullable', 'integer'],
        ]);

        $this->sectionService->create($exam, $validated);

        return back()->with('success', 'Section berhasil ditambahkan.');
    }

    public function update(Request $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'skill_id' => ['exists:skills,id'],
            'title' => ['string', 'max:255'],
            'order' => ['integer'],
            'duration_minutes' => ['nullable', 'integer'],
            'instructions' => ['nullable', 'string'],
            'total_questions' => ['nullable', 'integer'],
        ]);

        $this->sectionService->update($section, $validated);

        return back()->with('success', 'Section diperbarui.');
    }

    public function destroy(Exam $exam, ExamSection $section): RedirectResponse
    {
        $this->sectionService->delete($section);

        return back()->with('success', 'Section dihapus.');
    }
}
