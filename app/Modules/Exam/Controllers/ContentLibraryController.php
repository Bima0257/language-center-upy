<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\Tag;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentLibraryController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Instructor/CreateQuestion', [
            'exams' => Exam::with('sections')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', [
            'question' => $question->load(['questionGroup.section.exam', 'tags', 'creator', 'updater']),
            'exams' => Exam::with('sections')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $examId = $request->input('exam_id');
        $sectionType = $request->input('section_type');
        $questionType = $request->input('question_type');
        $difficulty = $request->input('difficulty');
        $status = $request->input('status');
        $search = $request->input('search');
        $tagId = $request->input('tag_id');

        $questions = Question::with(['questionGroup.section.exam', 'tags', 'creator'])
            ->when($examId, fn ($q) => $q->whereHas('questionGroup.section.exam', fn ($sq) => $sq->where('id', $examId)))
            ->when($sectionType, fn ($q) => $q->whereHas('questionGroup.section', fn ($sq) => $sq->where('skill', $sectionType)))
            ->when($questionType, fn ($q) => $q->where('type', $questionType))
            ->when($difficulty, fn ($q) => $q->where('difficulty', $difficulty))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tagId, fn ($q) => $q->whereHas('tags', fn ($sq) => $sq->where('id', $tagId)))
            ->when($search, fn ($q) => $q->where('question_text', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Instructor/ContentLibrary', [
            'questions' => $questions,
            'exams' => Exam::with('sections')->get(),
            'tags' => Tag::orderBy('type')->orderBy('name')->get(),
            'filters' => [
                'exam_id' => $examId,
                'section_type' => $sectionType,
                'question_type' => $questionType,
                'difficulty' => $difficulty,
                'status' => $status,
                'tag_id' => $tagId,
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreLibraryQuestionRequest $request): RedirectResponse
    {
        $section = ExamSection::where('exam_id', $request->exam_id)
            ->where('skill', $request->section_type)
            ->first();

        if (! $section) {
            $section = ExamSection::create([
                'exam_id' => $request->exam_id,
                'skill' => $request->section_type,
                'type' => $request->section_type,
                'title' => $request->section_type === 'reading' ? 'Reading Section' : ucfirst($request->section_type) . ' Section',
                'order' => ExamSection::where('exam_id', $request->exam_id)->count() + 1,
            ]);
        }

        $groupTitle = $request->group_title ?: ($request->section_type === 'reading' ? 'Passage' : 'Group') . ' ' . ($section->questionGroups()->count() + 1);

        $group = $section->questionGroups()
            ->where('title', $groupTitle)
            ->first();

        if (! $group) {
            $group = $section->questionGroups()->create([
                'title' => $groupTitle,
                'order' => $section->questionGroups()->count() + 1,
            ]);
        }

        $question = $group->questions()->create([
            'type' => $request->type,
            'question_text' => $request->question_text,
            'options' => $request->options,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points ?? 1,
            'order' => $group->questions()->count() + 1,
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
            'type' => ['required', 'in:multiple_choice,multi_select,order,matching,fill_blank,essay,speaking,true_false,dictation,error_id'],
            'question_text' => ['required', 'string'],
            'options' => ['nullable', 'json'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['integer', 'min:1'],
            'difficulty' => ['in:easy,medium,hard'],
            'status' => ['in:draft,active,archived'],
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
}
