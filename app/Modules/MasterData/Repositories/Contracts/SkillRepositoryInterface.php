<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;

interface SkillRepositoryInterface
{
    /**
     * @return Collection<int, Skill>
     */
    public function allOrdered(): Collection;

    /**
     * @return Collection<int, Skill>
     */
    public function allActiveOrdered(): Collection;

    public function find(int $id): ?Skill;

    public function findByCode(string $code): ?Skill;

    public function create(array $data): Skill;

    public function update(Skill $skill, array $data): Skill;

    public function delete(Skill $skill): void;

    public function nextOrder(): int;
}
