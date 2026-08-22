<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\Question;
use App\Models\QuestionBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface QuestionRepositoryInterface
{
    public function paginateWithFilters(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function emptyPaginator(int $perPage = 20): LengthAwarePaginator;

    public function find(int $id): ?Question;

    public function findWithRelations(int $id): ?Question;

    public function create(array $data): Question;

    public function createMany(array $rows): void;

    public function update(Question $question, array $data): Question;

    public function delete(Question $question): void;

    public function bulkUpdateStatus(array $ids, array $data): int;

    public function maxOrderForPassage(int $passageId): int;

    /**
     * @return Collection<int, Question>
     */
    public function previewByPassage(int $passageId): Collection;

    /**
     * @return Collection<int, Question>
     */
    public function approvedBySkillForExamType(string $skill, ?int $examTypeId, ?int $bankId = null): Collection;

    /**
     * @return array<int>
     */
    public function approvedIdsWhereIn(array $ids, string $skill, ?int $examTypeId, ?int $bankId = null): array;

    /**
     * @return Collection<int, Question>
     */
    public function findByIdsWithPassage(array $ids): Collection;

    /**
     * Soal approved milik bank+skill tertentu yang belum terpasang di section.
     *
     * @param  array<int>  $excludeIds
     * @return Collection<int, Question>
     */
    public function approvedByBankAndSkillNotIn(int $bankId, string $skill, array $excludeIds): Collection;

    /**
     * @return Collection<int, Question>
     */
    public function fallbackApprovedBySkills(array $skills, ?int $examTypeId, ?int $bankId, array $excludeIds): Collection;

    /**
     * @return Collection<int, QuestionBank>
     */
    public function banksWithApprovedBySkillAndExamType(string $skill, ?int $examTypeId, ?int $bankId = null): Collection;

    public function count(): int;

    public function countDraft(): int;

    /**
     * @return Collection<int, Question>
     */
    public function pendingDrafts(int $limit = 10): Collection;

    public function countBySkill(): \Illuminate\Support\Collection;

    public function countByStatus(): Collection;

    public function countByBank(): Collection;
}
