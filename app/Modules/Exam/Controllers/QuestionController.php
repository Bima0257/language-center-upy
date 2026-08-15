<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkImportFileRequest;
use App\Http\Requests\Exam\BulkImportQuestionsRequest;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Models\Question;
use App\Modules\Exam\Services\QuestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function __construct(
        private QuestionService $questionService,
    ) {}

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $this->questionService->store($request->validated());

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function bulkStore(BulkImportQuestionsRequest $request): RedirectResponse
    {
        $imported = $this->questionService->bulkStore($request->validated('questions'));

        return back()->with('success', "{$imported} soal berhasil diimpor.");
    }

    public function update(StoreQuestionRequest $request, Question $question): RedirectResponse
    {
        $this->questionService->update($question, $request->validated());

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->questionService->delete($question);

        return back()->with('success', 'Soal dihapus.');
    }

    public function importFile(BulkImportFileRequest $request): RedirectResponse
    {
        try {
            $this->questionService->importFile($request->file('file'));
        } catch (\Throwable $e) {
            Log::warning('Import soal dari file gagal', ['error' => $e->getMessage()]);

            return back()->with('error', 'Gagal mengimpor file. Pastikan format file sesuai template yang disediakan.');
        }

        return back()->with('success', 'Soal berhasil diimpor dari file.');
    }
}
