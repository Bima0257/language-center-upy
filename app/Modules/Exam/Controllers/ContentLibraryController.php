<?php

namespace App\Modules\Exam\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\ReviewQuestionRequest;
use App\Http\Requests\Exam\StoreLibraryQuestionRequest;
use App\Models\ExamType;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(): Response
    {
        return Inertia::render('Instructor/CreateQuestion', [
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill_id')->orderBy('order')->get(),
        ]);
    }

    public function edit(Question $question): Response
    {
        return Inertia::render('Instructor/EditQuestion', [
            'question' => $question->load(['passage', 'questionBank', 'skill', 'skillPart', 'creator', 'updater', 'reviewer']),
            'questionBanks' => QuestionBank::where('is_active', true)->orderBy('name')->get(),
            'skills' => Skill::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'parts' => SkillPart::where('is_active', true)->orderBy('skill_id')->orderBy('order')->get(),
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
        $partId = $request->input('part_id');

        // Soal hanya dimuat setelah bank soal dipilih
        $questions = $questionBankId
            ? Question::with(['passage', 'questionBank', 'skill', 'skillPart', 'creator', 'reviewer'])
                ->when($skillId, fn ($q) => $q->where('skill_id', $skillId))
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
            'skills' => Skill::with('examType')->where('is_active', true)->orderBy('name')->get(),
            'parts' => SkillPart::with('skill')->where('is_active', true)->orderBy('skill_id')->orderBy('order')->get(),
            'passages' => Passage::orderBy('title')->get(),
            'statuses' => self::STATUSES,
            'selectedPassage' => $selectedPassage,
            'filters' => [
                'skill_id' => $skillId,
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

        $passage = $passageId ? Passage::find($passageId) : null;
        $bank = QuestionBank::with('examType')->findOrFail($request->question_bank_id);

        // Order starts at max(order) + 1 when attaching to an existing passage
        $order = 1;
        if ($passageId) {
            $order = (Question::where('passage_id', $passageId)->max('order') ?? 0) + 1;
        }

        foreach ($request->input('questions', []) as $q) {
            $skill = Skill::find($q['skill_id'] ?? null);
            if (! $skill) {
                throw ValidationException::withMessages(['questions' => 'Skill tidak valid.']);
            }

            $part = SkillPart::find($q['skill_part_id'] ?? null);
            if (! $part || $part->skill_id !== $skill->id) {
                throw ValidationException::withMessages(['questions' => 'Part harus milik skill yang sama dengan soal.']);
            }

            if ($skill->exam_type_id !== $bank->exam_type_id) {
                throw ValidationException::withMessages(['questions' => 'Skill harus se-kategori dengan bank soal yang dipilih.']);
            }

            $isListening = $skill->code === 'listening';

            $audioUrl = null;
            $imageUrl = null;

            if (! empty($q['audio_file']) && $q['audio_file'] instanceof \Illuminate\Http\UploadedFile) {
                $audioPath = $q['audio_file']->store('questions/audio', 'public');
                $audioUrl = $this->audioCompression->compress('public', $audioPath) ?? $audioPath;
            }

            if (! empty($q['image_file']) && $q['image_file'] instanceof \Illuminate\Http\UploadedFile) {
                $imagePath = $q['image_file']->store('questions/images', 'public');
                $imageUrl = $this->imageCompression->compress('public', $imagePath) ?? $imagePath;
            }

            if ($isListening) {
                if (! $audioUrl && ! ($passage && $passage->audio_url)) {
                    throw ValidationException::withMessages(['questions' => 'Soal listening wajib memiliki audio, baik di soal maupun di passage.']);
                }

                if ($passageId && $passage && ! $passage->audio_url) {
                    throw ValidationException::withMessages(['questions' => 'Passage untuk soal listening wajib bertipe audio.']);
                }
            } else {
                $optionEmpty = collect(['option_a', 'option_b', 'option_c', 'option_d'])
                    ->contains(fn ($field) => empty(trim($q[$field] ?? '')));

                if (empty(trim($q['question_text'] ?? '')) || $optionEmpty) {
                    throw ValidationException::withMessages(['questions' => 'Soal reading wajib memiliki teks soal dan seluruh pilihan jawaban.']);
                }
            }

            Question::create([
                'question_bank_id' => $request->question_bank_id,
                'skill_id' => $skill->id,
                'skill_part_id' => $part->id,
                'passage_id' => $passageId,
                'type' => 'multiple_choice',
                'question_text' => $q['question_text'] ?? '',
                'option_a' => $q['option_a'] ?? '',
                'option_b' => $q['option_b'] ?? '',
                'option_c' => $q['option_c'] ?? '',
                'option_d' => $q['option_d'] ?? '',
                'correct_answer' => $q['correct_answer'],
                'audio_url' => $audioUrl,
                'image_url' => $imageUrl,
                'order' => $order++,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
        }

        $count = count($request->input('questions', []));

        return to_route('content-library.index')
            ->with('success', "{$count} soal berhasil ditambahkan ke bank soal.");
    }

    public function preview(Question $question): Response
    {
        $question->load(['passage', 'skill', 'skillPart', 'questionBank']);

        $questions = $question->passage_id
            ? Question::with(['passage', 'skill', 'skillPart'])
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
            'skill_id' => ['required', 'exists:skills,id'],
            'skill_part_id' => ['required', 'exists:skill_parts,id'],
            'passage_id' => ['nullable', 'exists:passages,id'],
            'question_text' => ['nullable', 'string'],
            'option_a' => ['nullable', 'string'],
            'option_b' => ['nullable', 'string'],
            'option_c' => ['nullable', 'string'],
            'option_d' => ['nullable', 'string'],
            'correct_answer' => ['required', 'string', 'max:1', 'in:A,B,C,D'],
            'audio_file' => ['nullable', 'file', 'mimes:mp3,wav,ogg,m4a', 'max:51200'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ]);

        $skill = Skill::find($validated['skill_id']);
        $part = SkillPart::find($validated['skill_part_id']);
        $bank = QuestionBank::with('examType')->findOrFail($validated['question_bank_id']);

        if (! $skill || ! $part || $part->skill_id !== $skill->id) {
            throw ValidationException::withMessages(['skill_part_id' => 'Part harus milik skill yang sama dengan soal.']);
        }

        if ($skill->exam_type_id !== $bank->exam_type_id) {
            throw ValidationException::withMessages(['skill_id' => 'Skill harus se-kategori dengan bank soal yang dipilih.']);
        }

        $isListening = $skill->code === 'listening';

        if ($request->hasFile('audio_file')) {
            $audioPath = $request->file('audio_file')->store('questions/audio', 'public');
            $validated['audio_url'] = $this->audioCompression->compress('public', $audioPath) ?? $audioPath;
        }

        if ($request->hasFile('image_file')) {
            $imagePath = $request->file('image_file')->store('questions/images', 'public');
            $validated['image_url'] = $this->imageCompression->compress('public', $imagePath) ?? $imagePath;
        }

        if ($isListening) {
            $hasAudio = ! empty($validated['audio_url']) || $question->audio_url
                || ($question->passage && $question->passage->audio_url);

            if (! $hasAudio) {
                throw ValidationException::withMessages(['audio_file' => 'Soal listening wajib memiliki audio, baik di soal maupun di passage.']);
            }
        } else {
            $optionEmpty = collect(['option_a', 'option_b', 'option_c', 'option_d'])
                ->contains(fn ($field) => empty(trim($validated[$field] ?? '')));

            if (empty(trim($validated['question_text'] ?? '')) || $optionEmpty) {
                throw ValidationException::withMessages(['question_text' => 'Soal reading wajib memiliki teks soal dan seluruh pilihan jawaban.']);
            }
        }

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
