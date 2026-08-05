<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ReviewQuestionRequest;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class ContentLibraryController extends Controller
{
    private const STATUSES = ['draft', 'approved', 'rejected'];

    public function __construct(
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Instructor/CreateQuestion', [
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', [
            'question' => $question->load(['passage', 'questionBank', 'skill', 'creator', 'updater', 'reviewer']),
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::where('is_active', true)->orderBy('name')->get(),
            'passages' => Passage::orderBy('title')->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $skillId = $request->input('skill_id');
        $questionBankId = $request->input('question_bank_id');
        $status = $request->input('status');
        $search = $request->input('search');
        $passageId = $request->input('passage_id');

        $questions = Question::with(['passage', 'questionBank', 'skill', 'creator', 'reviewer'])
            ->when($skillId, fn ($q) => $q->where('skill_id', $skillId))
            ->when($questionBankId, fn ($q) => $q->where('question_bank_id', $questionBankId))
            ->when($status, fn ($q) => $q->where('status', $status))
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
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::where('is_active', true)->orderBy('name')->get(),
            'passages' => Passage::orderBy('title')->get(),
            'statuses' => self::STATUSES,
            'selectedPassage' => $selectedPassage,
            'filters' => [
                'skill_id' => $skillId,
                'question_bank_id' => $questionBankId,
                'status' => $status,
                'passage_id' => $passageId,
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreLibraryQuestionRequest $request): RedirectResponse
    {
        $passageId = $request->input('passage_id');

        if (! $passageId && $request->filled('new_passage_title')) {
            $audioUrl = null;
            $imageUrl = null;

            if ($request->hasFile('new_passage_audio_file')) {
                $audioPath = $request->file('new_passage_audio_file')->store('passages/audio', 'public');
                $compressedPath = $this->audioCompression->compress('public', $audioPath);
                $audioUrl = $compressedPath ?? $audioPath;
            }

            if ($request->hasFile('new_passage_image_file')) {
                $imagePath = $request->file('new_passage_image_file')->store('passages/images', 'public');
                $compressedPath = $this->imageCompression->compress('public', $imagePath);
                $imageUrl = $compressedPath ?? $imagePath;
            }

            $passage = Passage::create([
                'title' => $request->new_passage_title,
                'type' => $request->new_passage_type ?? 'text',
                'content_text' => $request->filled('new_passage_content_text')
                    ? Purifier::clean($request->new_passage_content_text)
                    : null,
                'audio_url' => $audioUrl,
                'image_url' => $imageUrl,
            ]);

            $passageId = $passage->id;
        }

        // Order starts at max(order) + 1 when attaching to an existing passage
        $order = 1;
        if ($passageId) {
            $order = (Question::where('passage_id', $passageId)->max('order') ?? 0) + 1;
        }

        foreach ($request->input('questions', []) as $q) {
            Question::create([
                'question_bank_id' => $request->question_bank_id,
                'skill_id' => $q['skill_id'] ?? null,
                'passage_id' => $passageId,
                'type' => 'multiple_choice',
                'question_text' => $q['question_text'],
                'option_a' => $q['option_a'],
                'option_b' => $q['option_b'],
                'option_c' => $q['option_c'],
                'option_d' => $q['option_d'],
                'correct_answer' => $q['correct_answer'],
                'order' => $order++,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        }

        $count = count($request->input('questions', []));

        return to_route('content-library.index')
            ->with('success', "{$count} soal berhasil ditambahkan ke bank soal.");
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill_id' => ['required', 'exists:skills,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['required', 'string'],
            'option_a' => ['required', 'string'],
            'option_b' => ['required', 'string'],
            'option_c' => ['required', 'string'],
            'option_d' => ['required', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ]);

        $validated['updated_by'] = auth()->id();
        // Setiap edit oleh instructor mengembalikan soal ke draft untuk direview ulang
        $validated['status'] = 'draft';
        $question->update($validated);

        return to_route('content-library.index')->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
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
