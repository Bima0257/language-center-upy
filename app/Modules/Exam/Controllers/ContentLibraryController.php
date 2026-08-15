<?php

namespace App\Modules\Exam\Controllers;

use App\Enums\SkillCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ReviewQuestionRequest;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use App\Models\ExamType;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\SkillPart;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function create(Request $request): Response
    {
        $preselectedBankId = $request->query('question_bank_id');

        return Inertia::render('Instructor/CreateQuestion', [
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skillOptions' => SkillCode::options(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill')->orderBy('order')->get(),
            'preselectedBankId' => $preselectedBankId
                && QuestionBank::whereKey($preselectedBankId)->where('is_active', true)->exists()
                    ? (int) $preselectedBankId
                    : null,
            'returnFilters' => $this->filterQuery($request),
        ]);
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', [
            'question' => $question->load(['passage', 'questionBank', 'skillPart', 'creator', 'updater', 'reviewer']),
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skillOptions' => SkillCode::options(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill')->orderBy('order')->get(),
            'passages' => Passage::orderBy('title')->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $skill = $request->input('skill');
        $questionBankId = $request->input('question_bank_id');
        $status = $request->input('status');
        $search = $request->input('search');
        $passageId = $request->input('passage_id');
        $partId = $request->input('part_id');

        // Soal hanya dimuat setelah bank soal dipilih
        $questions = $questionBankId
            ? Question::with(['passage', 'questionBank', 'skillPart', 'creator', 'reviewer'])
                ->when($skill, fn ($q) => $q->where('skill', $skill))
                ->when($questionBankId, fn ($q) => $q->where('question_bank_id', $questionBankId))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($passageId, fn ($q) => $q->where('passage_id', $passageId))
                ->when($partId, fn ($q) => $q->where('skill_part_id', $partId))
                ->when($search, fn ($q) => $q->where('question_text', 'like', "%{$search}%"))
                ->orderBy('created_at', 'desc')
                ->paginate(20)
                ->withQueryString()
            : Question::query()->whereRaw('1 = 0')->paginate(20);

        $selectedPassage = null;
        if ($passageId) {
            $selectedPassage = Passage::find($passageId);
        }

        return Inertia::render('Instructor/ContentLibrary', [
            'questions' => $questions,
            'examTypes' => ExamType::where('is_active', true)->orderBy('name')->get(),
            'questionBanks' => QuestionBank::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'skillOptions' => SkillCode::options(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill')->orderBy('order')->get(),
            'passages' => Passage::orderBy('title')->get(),
            'statuses' => self::STATUSES,
            'selectedPassage' => $selectedPassage,
            'filters' => [
                'skill' => $skill,
                'question_bank_id' => $questionBankId,
                'status' => $status,
                'passage_id' => $passageId,
                'part_id' => $partId,
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreLibraryQuestionRequest $request): RedirectResponse
    {
        $storedFiles = [];

        try {
            DB::transaction(function () use ($request, &$storedFiles) {
                $passageId = $request->input('passage_id');

                if (! $passageId && $request->filled('new_passage_title')) {
                    $audioUrl = null;
                    $imageUrl = null;

                    if ($request->hasFile('new_passage_audio_file')) {
                        $audioPath = $request->file('new_passage_audio_file')->store('passages/audio', 'public');
                        $compressedPath = $this->audioCompression->compress('public', $audioPath);
                        $audioUrl = $compressedPath ?? $audioPath;
                        $storedFiles[] = $audioUrl;
                    }

                    if ($request->hasFile('new_passage_image_file')) {
                        $imagePath = $request->file('new_passage_image_file')->store('passages/images', 'public');
                        $compressedPath = $this->imageCompression->compress('public', $imagePath);
                        $imageUrl = $compressedPath ?? $imagePath;
                        $storedFiles[] = $imageUrl;
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

                $passage = $passageId ? Passage::find($passageId) : null;

                // Order starts at max(order) + 1 when attaching to an existing passage
                $order = 1;
                if ($passageId) {
                    $order = (Question::where('passage_id', $passageId)->max('order') ?? 0) + 1;
                }

                foreach ($request->input('questions', []) as $q) {
                    $skill = SkillCode::tryFrom($q['skill'] ?? '');
                    if (! $skill) {
                        throw ValidationException::withMessages(['questions' => 'Skill tidak valid.']);
                    }

                    $part = SkillPart::find($q['skill_part_id'] ?? null);
                    if (! $part || $part->skill !== $skill) {
                        throw ValidationException::withMessages(['questions' => 'Part harus milik skill yang sama dengan soal.']);
                    }

                    $isAudio = $skill === SkillCode::LISTENING;

                    if ($isAudio) {
                        if (! $passageId || ! $passage || ! $passage->audio_url) {
                            throw ValidationException::withMessages(['questions' => 'Soal listening wajib menggunakan passage yang memiliki audio.']);
                        }
                    } else {
                        $optionEmpty = collect(['option_a', 'option_b', 'option_c', 'option_d'])
                            ->contains(fn ($field) => empty(trim($q[$field] ?? '')));

                        if (empty(trim($q['question_text'] ?? '')) || $optionEmpty) {
                            throw ValidationException::withMessages(['questions' => 'Soal tipe teks wajib memiliki teks soal dan seluruh pilihan jawaban.']);
                        }
                    }

                    Question::create([
                        'question_bank_id' => $request->question_bank_id,
                        'skill' => $skill,
                        'skill_part_id' => $part->id,
                        'passage_id' => $passageId,
                        'type' => 'multiple_choice',
                        'question_text' => $q['question_text'] ?? '',
                        'option_a' => $q['option_a'] ?? '',
                        'option_b' => $q['option_b'] ?? '',
                        'option_c' => $q['option_c'] ?? '',
                        'option_d' => $q['option_d'] ?? '',
                        'correct_answer' => $q['correct_answer'],
                        'order' => $order++,
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $file) {
                Storage::disk('public')->delete($file);
            }

            throw $e;
        }

        $count = count($request->input('questions', []));

        $filters = array_filter([
            'question_bank_id' => $request->input('question_bank_id'),
            'skill' => $request->input('_return_skill') ?: $request->input('questions.0.skill'),
            'part_id' => $request->input('_return_part_id'),
            'status' => $request->input('_return_status'),
            'search' => $request->input('_return_search'),
        ], fn ($value) => ! empty($value));

        return to_route('content-library.index', $filters)
            ->with('success', "{$count} soal berhasil ditambahkan ke bank soal.");
    }

    public function preview(Question $question): Response
    {
        $question->load(['passage', 'skillPart', 'questionBank']);

        $questions = $question->passage_id
            ? Question::with(['passage', 'skillPart'])
                ->where('passage_id', $question->passage_id)
                ->orderBy('order')
                ->get()
            : collect([$question]);

        $index = $questions->search(fn ($q) => $q->id === $question->id);
        $index = $index === false ? 0 : $index;

        return Inertia::render('Instructor/QuestionPreview', [
            'questions' => $questions->values(),
            'index' => $index,
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'skill' => ['required', Rule::enum(SkillCode::class)],
            'skill_part_id' => ['required', 'exists:skill_parts,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['nullable', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
        ]);

        $skill = SkillCode::tryFrom($validated['skill']);
        $part = SkillPart::find($validated['skill_part_id']);

        if (! $skill || ! $part || $part->skill !== $skill) {
            throw ValidationException::withMessages(['skill_part_id' => 'Part harus milik skill yang sama dengan soal.']);
        }

        if ($skill === SkillCode::LISTENING) {
            $passage = $validated['passage_id'] ? Passage::find($validated['passage_id']) : null;

            if (! $passage || ! $passage->audio_url) {
                throw ValidationException::withMessages(['passage_id' => 'Soal listening wajib menggunakan passage yang memiliki audio.']);
            }
        } else {
            $optionEmpty = collect(['option_a', 'option_b', 'option_c', 'option_d'])
                ->contains(fn ($field) => empty(trim($validated[$field] ?? '')));

            if (empty(trim($validated['question_text'] ?? '')) || $optionEmpty) {
                throw ValidationException::withMessages(['question_text' => 'Soal tipe teks wajib memiliki teks soal dan seluruh pilihan jawaban.']);
            }
        }

        $validated['updated_by'] = auth()->id();
        // Setiap edit oleh instructor mengembalikan soal ke draft untuk direview ulang
        $validated['status'] = 'draft';
        $question->update($validated);

        return to_route('content-library.index', [
            'question_bank_id' => $question->question_bank_id,
            'skill' => $question->skill->value,
        ])->with('success', 'Soal berhasil diperbarui.');
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

    private function filterQuery(Request $request): array
    {
        return array_filter(
            $request->only(['question_bank_id', 'skill', 'part_id', 'status', 'search']),
            fn ($value) => $value !== null && $value !== ''
        );
    }
}
