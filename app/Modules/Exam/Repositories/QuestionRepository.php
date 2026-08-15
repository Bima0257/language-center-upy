<?php

namespace App\Modules\Exam\Repositories;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $skill = $filters['skill'] ?? null;
        $questionBankId = $filters['question_bank_id'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        $passageId = $filters['passage_id'] ?? null;
        $partId = $filters['part_id'] ?? null;

        return Question::with(['passage', 'questionBank', 'skillPart', 'creator', 'reviewer'])
            ->when($skill, fn ($q) => $q->where('skill', $skill))
            ->when($questionBankId, fn ($q) => $q->where('question_bank_id', $questionBankId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($passageId, fn ($q) => $q->where('passage_id', $passageId))
            ->when($partId, fn ($q) => $q->where('skill_part_id', $partId))
            ->when($search, fn ($q) => $q->where('question_text', 'like', "%{$search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function emptyPaginator(int $perPage = 20): LengthAwarePaginator
    {
        return Question::query()->whereRaw('1 = 0')->paginate($perPage);
    }

    public function find(int $id): ?Question
    {
        return Question::find($id);
    }

    public function findWithRelations(int $id): ?Question
    {
        return Question::with(['passage', 'questionBank', 'skillPart', 'creator', 'updater', 'reviewer'])->find($id);
    }

    public function create(array $data): Question
    {
        return Question::create($data);
    }

    public function createMany(array $rows): void
    {
        foreach ($rows as $row) {
            Question::create($row);
        }
    }

    public function update(Question $question, array $data): Question
    {
        $question->update($data);

        return $question->fresh();
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    public function bulkUpdateStatus(array $ids, array $data): int
    {
        return Question::whereIn('id', $ids)->update($data);
    }

    public function maxOrderForPassage(int $passageId): int
    {
        return Question::where('passage_id', $passageId)->max('order') ?? 0;
    }

    public function previewByPassage(int $passageId): Collection
    {
        return Question::with(['passage', 'skillPart'])
            ->where('passage_id', $passageId)
            ->orderBy('order')
            ->get();
    }

    public function approvedBySkillForExamType(string $skill, ?int $examTypeId, ?int $bankId = null): Collection
    {
        return Question::where('skill', $skill)
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int>
     */
    public function approvedIdsWhereIn(array $ids, string $skill, ?int $examTypeId, ?int $bankId = null): array
    {
        return Question::whereIn('id', $ids)
            ->where('status', 'approved')
            ->where('skill', $skill)
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->pluck('id')
            ->all();
    }

    public function findByIdsWithPassage(array $ids): Collection
    {
        return Question::with('passage')
            ->whereIn('id', $ids)
            ->where('status', 'approved')
            ->get();
    }

    public function approvedByBankAndSkillNotIn(int $bankId, string $skill, array $excludeIds): Collection
    {
        return Question::with(['passage', 'questionBank'])
            ->where('question_bank_id', $bankId)
            ->where('skill', $skill)
            ->where('status', 'approved')
            ->whereNotIn('id', $excludeIds)
            ->orderBy('id')
            ->get();
    }

    public function fallbackApprovedBySkills(array $skills, ?int $examTypeId, ?int $bankId, array $excludeIds): Collection
    {
        return Question::with('passage')
            ->whereIn('skill', $skills)
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->whereNotIn('id', $excludeIds)
            ->get();
    }

    public function banksWithApprovedBySkillAndExamType(string $skill, ?int $examTypeId): Collection
    {
        $bankIds = Question::where('skill', $skill)
            ->where('status', 'approved')
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->distinct()
            ->pluck('question_bank_id')
            ->all();

        return QuestionBank::whereIn('id', $bankIds)->orderBy('name')->get();
    }

    public function count(): int
    {
        return Question::count();
    }

    public function countDraft(): int
    {
        return Question::where('status', 'draft')->count();
    }

    public function pendingDrafts(int $limit = 10): Collection
    {
        return Question::with(['passage', 'questionBank', 'creator'])
            ->where('status', 'draft')
            ->latest()
            ->take($limit)
            ->get();
    }

    public function countBySkill(): Collection
    {
        return Question::selectRaw('skill as label, COUNT(*) as count')
            ->whereNotNull('skill')
            ->groupBy('skill')
            ->orderByDesc('count')
            ->get();
    }

    public function countByStatus(): Collection
    {
        return Question::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    public function countByBank(): Collection
    {
        return Question::selectRaw('question_banks.name as label, COUNT(*) as count')
            ->leftJoin('question_banks', 'question_banks.id', '=', 'questions.question_bank_id')
            ->groupBy('question_banks.name')
            ->orderByDesc('count')
            ->get();
    }
}
