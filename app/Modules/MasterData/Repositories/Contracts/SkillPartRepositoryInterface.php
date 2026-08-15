<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\SkillPart;
use Illuminate\Database\Eloquent\Collection;

interface SkillPartRepositoryInterface
{
    public function find(int $id): ?SkillPart;

    public function allWithQuestionCounts(): Collection;

    /**
     * @return Collection<int, SkillPart>
     */
    public function allActiveOrderedBySkill(): Collection;

    /**
     * @return array<int>
     */
    public function idsBySkill(string $skill): array;

    /**
     * @return array<int>
     */
    public function orderedIdsBySkill(string $skill): array;

    public function create(array $data): SkillPart;

    public function update(SkillPart $skillPart, array $data): SkillPart;

    public function delete(SkillPart $skillPart): void;
}
