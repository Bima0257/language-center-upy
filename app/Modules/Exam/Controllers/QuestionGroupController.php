<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExamSection;
use App\Models\QuestionGroup;
use App\Modules\Exam\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestionGroupController extends Controller
{
    public function __construct(
        private QuestionService $questionService,
    ) {}

    public function store(Request $request, ExamSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'passage_text' => ['nullable', 'string'],
            'order' => ['required', 'integer'],
        ]);

        $this->questionService->createGroup($section, $validated);

        return back()->with('success', 'Grup soal berhasil ditambahkan.');
    }

    public function update(Request $request, QuestionGroup $group): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'passage_text' => ['nullable', 'string'],
            'order' => ['integer'],
        ]);

        $this->questionService->updateGroup($group, $validated);

        return back()->with('success', 'Grup soal diperbarui.');
    }

    public function destroy(QuestionGroup $group): RedirectResponse
    {
        $this->questionService->deleteGroup($group);

        return back()->with('success', 'Grup soal dihapus.');
    }
}
