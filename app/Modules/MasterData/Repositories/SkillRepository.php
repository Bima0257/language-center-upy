<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\Skill;
use App\Modules\MasterData\Repositories\Contracts\SkillRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SkillRepository implements SkillRepositoryInterface
{
    /**
     * @return Collection<int, Skill>
     */
    public function allOrdered(): Collection
    {
        return Skill::orderBy('order')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Skill>
     */
    public function allActiveOrdered(): Collection
    {
        return Skill::where('is_active', true)->orderBy('order')->orderBy('name')->get();
    }

    public function find(int $id): ?Skill
    {
        return Skill::find($id);
    }

    public function findByCode(string $code): ?Skill
    {
        return Skill::where('code', $code)->first();
    }

    public function create(array $data): Skill
    {
        return Skill::create($data);
    }

    public function update(Skill $skill, array $data): Skill
    {
        $skill->update($data);

        return $skill->fresh();
    }

    public function delete(Skill $skill): void
    {
        $skill->delete();
    }

    public function nextOrder(): int
    {
        return (Skill::max('order') ?? 0) + 1;
    }

    public function findMany(array $ids): Collection
    {
        return Skill::whereIn('id', $ids)->get();
    }
}
