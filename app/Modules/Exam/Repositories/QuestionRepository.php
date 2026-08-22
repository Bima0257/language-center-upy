<?php

namespace App\Modules\Exam\Repositories;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $skillId = $filters['skill_id'] ?? null;
        $questionBankId = $filters['question_bank_id'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        $passageId = $filters['passage_id'] ?? null;
        $partId = $filters['part_id'] ?? null;

        return Question::with(['passage', 'questionBank', 'skill', 'skillPart', 'creator', 'reviewer'])
            ->when($skillId, fn ($q) => $q->where('skill_id', $skillId))
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
        return Question::with(['passage', 'skill', 'skillPart'])
            ->where('passage_id', $passageId)
            ->orderBy('order')
            ->get();
    }

    public function approvedBySkillForExamType(string $skillCode, ?int $examTypeId, ?int $bankId = null): Collection
    {
        return Question::whereHas('skill', fn ($q) => $q->where('code', $skillCode))
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int>
     */
    public function approvedIdsWhereIn(array $ids, string $skillCode, ?int $examTypeId, ?int $bankId = null): array
    {
        return Question::whereIn('id', $ids)
            ->where('status', 'approved')
            ->whereHas('skill', fn ($q) => $q->where('code', $skillCode))
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

    public function approvedByBankAndSkillNotIn(int $bankId, string $skillCode, array $excludeIds): Collection
    {
        return Question::with(['passage', 'questionBank'])
            ->where('question_bank_id', $bankId)
            ->whereHas('skill', fn ($q) => $q->where('code', $skillCode))
            ->where('status', 'approved')
            ->whereNotIn('id', $excludeIds)
            ->orderBy('id')
            ->get();
    }

    public function fallbackApprovedBySkills(array $skillCodes, ?int $examTypeId, ?int $bankId, array $excludeIds): Collection
    {
        return Question::with('passage')
            ->whereHas('skill', fn ($q) => $q->whereIn('code', $skillCodes))
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->whereNotIn('id', $excludeIds)
            ->get();
    }

    public function banksWithApprovedBySkillAndExamType(string $skillCode, ?int $examTypeId, ?int $bankId = null): Collection
    {
        $bankIds = Question::whereHas('skill', fn ($q) => $q->where('code', $skillCode))
            ->where('status', 'approved')
            ->when($examTypeId, fn ($q) => $q->whereHas('questionBank', fn ($b) => $b->where('exam_type_id', $examTypeId)))
            ->when($bankId, fn ($q) => $q->where('question_bank_id', $bankId))
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

    public function countBySkill(): \Illuminate\Support\Collection
    {
        $rows = DB::select('
            SELECT skill_id, COUNT(*) as total
            FROM questions
            WHERE skill_id IS NOT NULL
            GROUP BY skill_id
            ORDER BY total DESC
        ');

        $skillIds = array_column($rows, 'skill_id');
        $skillMap = Skill::whereIn('id', $skillIds)->get()->keyBy('id');

        $result = new \Illuminate\Support\Collection;
        foreach ($rows as $row) {
            $skill = $skillMap->get($row->skill_id);

            $result->push([
                'label' => $skill->name ?? 'Unknown',
                'count' => (int) $row->total,
            ]);
        }

        return $result;
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
