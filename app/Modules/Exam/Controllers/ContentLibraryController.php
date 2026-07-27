<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ReviewQuestionRequest;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentLibraryController extends Controller
{
    private const STATUSES = ['draft', 'submitted', 'approved', 'rejected', 'archived'];

    public function create(Request $request): Response
    {
        $passageId = $request->input('passage_id');
        $preselectedPassage = null;

        if ($passageId) {
            $preselectedPassage = Passage::find($passageId);
        }

        return Inertia::render('Instructor/CreateQuestion', [
            'passages' => Passage::orderBy('title')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
            'preselectedPassage' => $preselectedPassage,
        ]);
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', [
            'question' => $question->load(['passage', 'tags', 'creator', 'updater', 'reviewer']),
            'passages' => Passage::orderBy('title')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $skill = $request->input('skill');
        $questionType = $request->input('question_type');
        $difficulty = $request->input('difficulty');
        $status = $request->input('status');
        $search = $request->input('search');
        $tagId = $request->input('tag_id');
        $passageId = $request->input('passage_id');

        $questions = Question::with(['passage', 'tags', 'creator', 'reviewer'])
            ->when($skill, fn ($q) => $q->where('skill', $skill))
            ->when($questionType, fn ($q) => $q->where('type', $questionType))
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tagId, fn ($q) => $q->whereHas('tags', fn ($sq) => $sq->where('id', $tagId)))
            ->when($passageId, fn ($q) => $q->where('passage_id', $passageId))
            ->when($search, fn ($q) => $q->where('question_text', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $selectedPassage = null;
        if ($passageId) {
            $selectedPassage = Passage::find($passageId);
        }

        return Inertia::render('Instructor/ContentLibrary', [
            'questions' => $questions,
            'passages' => Passage::orderBy('title')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
            'statuses' => self::STATUSES,
            'selectedPassage' => $selectedPassage,
            'filters' => [
                'skill' => $skill,
                'question_type' => $questionType,
                'difficulty' => $difficulty,
                'status' => $status,
                'tag_id' => $tagId,
                'passage_id' => $passageId,
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreLibraryQuestionRequest $request): RedirectResponse
    {
        $question = Question::create([
            'skill' => $request->skill,
            'passage_id' => $request->passage_id,
            'audio_file' => $request->audio_file,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'options' => $request->options,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points ?? 1,
            'order' => 0,
            'passage_reference' => $request->passage_reference,
            'difficulty' => $request->difficulty ?? 'medium',
            'status' => $request->status ?? 'draft',
            'explanation' => $request->explanation,
            'time_estimate' => $request->time_estimate,
            'created_by' => auth()->id(),
        ]);

        if ($request->filled('tags') && is_array($request->tags)) {
            $question->tags()->sync($request->tags);
        }

        return to_route('content-library.index')->with('success', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'skill' => ['required', 'string', 'in:reading,listening,speaking,writing,grammar,vocabulary'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'audio_file' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'question_text' => ['required', 'string'],
            'options' => ['nullable', 'json'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['integer', 'min:1'],
            'difficulty' => ['in:easy,medium,hard'],
            'status' => ['in:draft,submitted,approved,rejected,archived'],
            'explanation' => ['nullable', 'string'],
            'time_estimate' => ['nullable', 'integer'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
        ]);

        $validated['updated_by'] = auth()->id();
        $question->update($validated);

        if ($request->has('tags')) {
            $question->tags()->sync($request->tags ?? []);
        }

        return to_route('content-library.index')->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->tags()->detach();
        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function review(ReviewQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update([
            'status' => $request->status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Soal berhasil {$label}.");
    }

    public function bulkReview(ReviewQuestionRequest $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'Tidak ada soal yang dipilih.');
        }

        Question::whereIn('id', $ids)->update([
            'status' => $request->status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        $count = count($ids);

        return back()->with('success', "{$count} soal berhasil {$label}.");
    }
}
