<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\SkillPart;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SkillPartRepository implements SkillPartRepositoryInterface
{
    public function find(int $id): ?SkillPart
    {
        return SkillPart::find($id);
    }

    public function allWithQuestionCounts(): Collection
    {
        return SkillPart::with(['questionBank', 'skill'])
            ->withCount('questions')
            ->orderBy('question_bank_id')
            ->orderBy('skill_id')
            ->orderBy('order')
            ->get();
    }

    public function allActiveOrdered(): Collection
    {
        return SkillPart::with(['questionBank', 'skill'])
            ->where('is_active', true)
            ->orderBy('question_bank_id')
            ->orderBy('skill_id')
            ->orderBy('order')
            ->get();
    }

    public function allActiveByBank(int $bankId): Collection
    {
        return SkillPart::with('skill')
            ->where('question_bank_id', $bankId)
            ->where('is_active', true)
            ->orderBy('skill_id')
            ->orderBy('order')
            ->get();
    }

    /**
     * @return array<int>
     */
    public function idsByBankAndSkill(int $bankId, int $skillId): array
    {
        return SkillPart::where('question_bank_id', $bankId)
            ->where('skill_id', $skillId)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int>
     */
    public function orderedIdsByBankAndSkill(int $bankId, int $skillId): array
    {
        return SkillPart::where('question_bank_id', $bankId)
            ->where('skill_id', $skillId)
            ->orderBy('order')
            ->pluck('id')
            ->all();
    }

    public function create(array $data): SkillPart
    {
        return SkillPart::create($data);
    }

    public function update(SkillPart $skillPart, array $data): SkillPart
    {
        $skillPart->update($data);

        return $skillPart->fresh();
    }

    public function delete(SkillPart $skillPart): void
    {
        $skillPart->delete();
    }

    public function nextOrder(int $bankId, int $skillId): int
    {
        return (SkillPart::where('question_bank_id', $bankId)
            ->where('skill_id', $skillId)
            ->max('order') ?? 0) + 1;
    }

    public function findMany(array $ids): Collection
    {
        return SkillPart::whereIn('id', $ids)->get();
    }
}
