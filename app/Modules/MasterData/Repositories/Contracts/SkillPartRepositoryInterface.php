<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\SkillPart;
use Illuminate\Database\Eloquent\Collection;

interface SkillPartRepositoryInterface
{
    public function find(int $id): ?SkillPart;

    public function allWithQuestionCounts(): Collection;

    public function allActiveOrdered(): Collection;

    /**
     * @return Collection<int, SkillPart>
     */
    public function allActiveByBank(int $bankId): Collection;

    /**
     * @return array<int>
     */
    public function idsByBankAndSkill(int $bankId, string $skill): array;

    /**
     * @return array<int>
     */
    public function orderedIdsByBankAndSkill(int $bankId, string $skill): array;

    public function create(array $data): SkillPart;

    public function update(SkillPart $skillPart, array $data): SkillPart;

    public function delete(SkillPart $skillPart): void;

    /**
     * Urutan berikutnya untuk (bank, skill) — max(order) + 1.
     */
    public function nextOrder(int $bankId, string $skill): int;

    /**
     * @return Collection<int, SkillPart>
     */
    public function findMany(array $ids): Collection;
}
