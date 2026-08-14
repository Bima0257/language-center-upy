<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\BulkImportFileRequest;
use App\Http\Requests\Exam\BulkImportQuestionsRequest;
use App\Http\Requests\Exam\StoreQuestionRequest;
use App\Imports\QuestionsImport;
use App\Models\Question;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class QuestionController extends Controller
{
    public function __construct(
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        $validated = $this->storeMediaFiles($request);

        Question::create(array_merge(
            $validated,
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
        $validated = $request->validated();

        if ($request->hasFile('audio_file')) {
            if ($question->audio_url && ! Question::where('audio_url', $question->audio_url)->whereKeyNot($question->id)->exists()) {
                Storage::disk('public')->delete($question->audio_url);
            }
            $audioPath = $request->file('audio_file')->store('questions/audio', 'public');
            $validated['audio_url'] = $this->audioCompression->compress('public', $audioPath) ?? $audioPath;
        }

        if ($request->hasFile('image_file')) {
            if ($question->image_url && ! Question::where('image_url', $question->image_url)->whereKeyNot($question->id)->exists()) {
                Storage::disk('public')->delete($question->image_url);
            }
            $imagePath = $request->file('image_file')->store('questions/images', 'public');
            $validated['image_url'] = $this->imageCompression->compress('public', $imagePath) ?? $imagePath;
        }

        unset($validated['audio_file'], $validated['image_file']);

        $question->update(array_merge(
            $validated,
            ['updated_by' => auth()->id()]
        ));

        return back()->with('success', 'Soal diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        if ($question->audio_url && ! Question::where('audio_url', $question->audio_url)->whereKeyNot($question->id)->exists()) {
            Storage::disk('public')->delete($question->audio_url);
        }
        if ($question->image_url && ! Question::where('image_url', $question->image_url)->whereKeyNot($question->id)->exists()) {
            Storage::disk('public')->delete($question->image_url);
        }

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

    private function storeMediaFiles(StoreQuestionRequest $request): array
    {
        $validated = $request->validated();

        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('questions/audio', 'public');
            $validated['audio_url'] = $this->audioCompression->compress('public', $audioPath) ?? $audioPath;
        }

        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('questions/images', 'public');
            $validated['image_url'] = $this->imageCompression->compress('public', $imagePath) ?? $imagePath;
        }

        unset($validated['audio_file'], $validated['image_file']);

        return $validated;
    }
}
