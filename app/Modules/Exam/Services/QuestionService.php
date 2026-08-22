<?php

namespace App\Modules\Exam\Services;

use App\Enums\SkillCode;
use App\Imports\QuestionsImport;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Skill;
use App\Modules\Exam\Repositories\Contracts\PassageRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;

class QuestionService
{
    private const STATUSES = ['draft', 'approved', 'rejected'];

    public function __construct(
        private QuestionRepositoryInterface $questions,
        private QuestionBankRepositoryInterface $questionBanks,
        private PassageRepositoryInterface $passages,
        private SkillPartRepositoryInterface $skillParts,
        private ExamTypeRepositoryInterface $examTypes,
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function statuses(): array
    {
        return self::STATUSES;
    }

    public function createData(int $preselectedBankId, array $filters): array
    {
        return [
            'questionBanks' => $this->questionBanks->allActiveOrdered(),
            'skillOptions' => SkillCode::options(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'preselectedBankId' => $preselectedBankId > 0 && $this->questionBanks->existsActive($preselectedBankId)
                ? $preselectedBankId
                : null,
            'returnFilters' => $filters,
        ];
    }

    public function editData(Question $question): array
    {
        return [
            'question' => $this->questions->findWithRelations($question->id),
            'questionBanks' => $this->questionBanks->allActiveOrdered(),
            'skillOptions' => SkillCode::options(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'passages' => $this->passages->allOrdered(),
        ];
    }

    public function indexData(array $filters): array
    {
        $questionBankId = $filters['question_bank_id'] ?? null;

        $questions = $questionBankId
            ? $this->questions->paginateWithFilters($filters)
            : $this->questions->emptyPaginator();

        return [
            'questions' => $questions,
            'examTypes' => $this->examTypes->allActiveOrdered(),
            'questionBanks' => $this->questionBanks->allActiveWithExamTypeOrdered(),
            'skillOptions' => SkillCode::options(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'passages' => $this->passages->allOrdered(),
            'statuses' => self::STATUSES,
            'selectedPassage' => isset($filters['passage_id']) ? $this->passages->find($filters['passage_id']) : null,
            'filters' => $filters,
        ];
    }

    public function storeLibrary(array $validated, ?Request $request): int
    {
        $storedFiles = [];

        try {
            $count = DB::transaction(function () use ($validated, $request, &$storedFiles) {
                $passageId = $validated['passage_id'] ?? null;

                if (! $passageId && ! empty($validated['new_passage_title'])) {
                    $passage = $this->createPassageFromRequest($validated, $request, $storedFiles);
                    $passageId = $passage->id;
                }

                $passage = $passageId ? $this->passages->find($passageId) : null;

                $order = $passageId ? $this->questions->maxOrderForPassage($passageId) + 1 : 1;

                $rows = [];
                foreach ($validated['questions'] as $q) {
                    $skillId = $q['skill_id'];
                    $this->assertQuestionSkillMatchesPart(
                        $validated['question_bank_id'],
                        $skillId,
                        $q['skill_part_id'],
                        'questions',
                    );
                    $this->assertQuestionCompatibleWithPassage($skillId, $passage, $q, 'questions');

                    $rows[] = [
                        'question_bank_id' => $validated['question_bank_id'],
                        'skill_id' => $skillId,
                        'skill_part_id' => $q['skill_part_id'],
                        'passage_id' => $passageId,
                        'question_text' => $q['question_text'] ?? '',
                        'option_a' => $q['option_a'] ?? '',
                        'option_b' => $q['option_b'] ?? '',
                        'option_c' => $q['option_c'] ?? '',
                        'option_d' => $q['option_d'] ?? '',
                        'correct_answer' => $q['correct_answer'],
                        'order' => $order++,
                        'status' => 'draft',
                        'created_by' => auth()->id(),
                    ];
                }

                $this->questions->createMany($rows);

                return count($rows);
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $file) {
                Storage::disk('public')->delete($file);
            }

            throw $e;
        }

        return $count;
    }

    public function previewData(Question $question): array
    {
        $question = $this->questions->findWithRelations($question->id);

        $questions = $question->passage_id
            ? $this->questions->previewByPassage($question->passage_id)
            : new Collection([$question]);

        $index = $questions->search(fn ($q) => $q->id === $question->id);
        $index = $index === false ? 0 : $index;

        return [
            'questions' => $questions->values(),
            'index' => $index,
        ];
    }

    public function updateLibrary(Question $question, array $validated): void
    {
        $this->assertQuestionSkillMatchesPart(
            $validated['question_bank_id'],
            $validated['skill_id'],
            $validated['skill_part_id'],
            'skill_part_id',
        );

        $passage = null;
        if (isset($validated['passage_id']) && $validated['passage_id']) {
            $passage = $this->passages->find($validated['passage_id']);
            $this->assertQuestionCompatibleWithPassage($validated['skill_id'], $passage, $validated, 'passage_id');
        }

        $data = $validated;
        $data['updated_by'] = auth()->id();
        $data['status'] = 'draft';

        $this->questions->update($question, $data);
    }

    public function delete(Question $question): void
    {
        $this->questions->delete($question);
    }

    public function review(Question $question, array $data): void
    {
        $this->questions->update($question, [
            'status' => $data['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);
    }

    public function bulkReview(array $ids, array $data): int
    {
        $this->questions->bulkUpdateStatus($ids, [
            'status' => $data['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        return count($ids);
    }

    public function store(array $validated): void
    {
        $this->questions->create(array_merge(
            $validated,
            ['created_by' => auth()->id()]
        ));
    }

    public function bulkStore(array $rows): int
    {
        return DB::transaction(function () use ($rows) {
            $data = array_map(
                fn ($row) => array_merge($row, ['created_by' => auth()->id()]),
                $rows,
            );

            $this->questions->createMany($data);

            return count($data);
        });
    }

    public function update(Question $question, array $validated): void
    {
        $this->questions->update($question, array_merge(
            $validated,
            ['updated_by' => auth()->id()]
        ));
    }

    public function importFile(UploadedFile $file): void
    {
        Excel::import(new QuestionsImport, $file);
    }

    public function hasDuplicateWithin(array $items, callable $keyOf): bool
    {
        $keys = array_map($keyOf, $items);

        return count($keys) !== count(array_unique($keys));
    }

    private function createPassageFromRequest(array $validated, ?Request $request, array &$storedFiles): Passage
    {
        $audioUrl = null;
        $imageUrl = null;

        if ($request !== null && $request->hasFile('new_passage_audio_file')) {
            $audioPath = $request->file('new_passage_audio_file')->store('passages/audio', 'public');
            $compressedPath = $this->audioCompression->compress('public', $audioPath);
            $audioUrl = $compressedPath ?? $audioPath;
            $storedFiles[] = $audioUrl;
        }

        if ($request !== null && $request->hasFile('new_passage_image_file')) {
            $imagePath = $request->file('new_passage_image_file')->store('passages/images', 'public');
            $compressedPath = $this->imageCompression->compress('public', $imagePath);
            $imageUrl = $compressedPath ?? $imagePath;
            $storedFiles[] = $imageUrl;
        }

        return $this->passages->create([
            'title' => $validated['new_passage_title'],
            'type' => $validated['new_passage_type'] ?? 'text',
            'content_text' => ! empty($validated['new_passage_content_text'])
                ? Purifier::clean($validated['new_passage_content_text'])
                : null,
            'audio_url' => $audioUrl,
            'image_url' => $imageUrl,
        ]);
    }

    private function assertQuestionSkillMatchesPart(int $questionBankId, int $skillId, int $skillPartId, string $errorKey): void
    {
        $part = $this->skillParts->find($skillPartId);

        if ($part === null || $part->skill_id !== $skillId || $part->question_bank_id !== $questionBankId) {
            throw ValidationException::withMessages([$errorKey => 'Part harus milik bank soal dan skill yang sama dengan soal.']);
        }
    }

    private function assertQuestionCompatibleWithPassage(int $skillId, ?Passage $passage, array $questionData, string $errorKey): void
    {
        $skill = Skill::find($skillId);

        if ($skill && $skill->code === 'listening') {
            if ($passage === null || ! $passage->audio_url) {
                throw ValidationException::withMessages([$errorKey => 'Soal listening wajib menggunakan passage yang memiliki audio.']);
            }

            return;
        }

        $optionEmpty = collect(['option_a', 'option_b', 'option_c', 'option_d'])
            ->contains(fn ($field) => empty(trim($questionData[$field] ?? '')));

        if (empty(trim($questionData['question_text'] ?? '')) || $optionEmpty) {
            throw ValidationException::withMessages([$errorKey => 'Soal tipe teks wajib memiliki teks soal dan seluruh pilihan jawaban.']);
        }
    }
}
