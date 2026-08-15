<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkImportFileRequest;
use App\Http\Requests\Exam\BulkImportQuestionsRequest;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Imports\QuestionsImport;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        Question::create(array_merge(
            $request->validated(),
            ['type' => 'multiple_choice', 'created_by' => auth()->id()]
        ));

        return back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function bulkStore(BulkImportQuestionsRequest $request): RedirectResponse
    {
        $imported = DB::transaction(function () use ($request) {
            $imported = 0;

            foreach ($request->validated('questions') as $question) {
                Question::create(array_merge(
                    $question,
                    ['type' => 'multiple_choice', 'created_by' => auth()->id()]
                ));
                $imported++;
            }

            return $imported;
        });

        return back()->with('success', "{$imported} soal berhasil diimpor.");
    }

    public function update(StoreQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update(array_merge(
            $request->validated(),
            ['updated_by' => auth()->id()]
        ));

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();

        return back()->with('success', 'Soal dihapus.');
    }

    public function importFile(BulkImportFileRequest $request): RedirectResponse
    {
        try {
            Excel::import(new QuestionsImport, $request->file('file'));
        } catch (\Throwable $e) {
            Log::warning('Import soal dari file gagal', ['error' => $e->getMessage()]);

            return back()->with('error', 'Gagal mengimpor file. Pastikan format file sesuai template yang disediakan.');
        }

        return back()->with('success', 'Soal berhasil diimpor dari file.');
    }
}
