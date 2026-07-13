<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\QuestionsImport;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Modules\Exam\Actions\BulkImportQuestions;
use App\Modules\Exam\Services\QuestionService;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Http\Requests\Exam\BulkImportQuestionsRequest;
use App\Http\Requests\Exam\BulkImportFileRequest;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionService $questionService,
        private BulkImportQuestions $bulkImport,
    ) {}

    public function store(StoreQuestionRequest $request, QuestionGroup $group): RedirectResponse
    {
        $this->questionService->createQuestion($group, $request->validated());

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function bulkStore(BulkImportQuestionsRequest $request, QuestionGroup $group): RedirectResponse
    {
        $result = $this->bulkImport->execute($group, $request->validated('questions'));

        return back()->with('success', "{$result->imported} soal berhasil diimpor.");
    }

    public function update(StoreQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questionService->updateQuestion($question, $request->validated());

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->questionService->deleteQuestion($question);

        return back()->with('success', 'Soal dihapus.');
    }

    public function importFile(BulkImportFileRequest $request, QuestionGroup $group): RedirectResponse
    {
        $lastOrder = $group->questions()->max('order') ?? 0;

        Excel::import(new QuestionsImport($group, $lastOrder), $request->file('file'));

        return back()->with('success', 'Soal berhasil diimpor dari file.');
    }
}
