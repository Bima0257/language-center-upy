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
        return SkillPart::with('questionBank')
            ->withCount('questions')
            ->orderBy('question_bank_id')
            ->orderBy('skill')
            ->orderBy('order')
            ->get();
    }

    public function allActiveOrdered(): Collection
    {
        return SkillPart::with('questionBank')
            ->where('is_active', true)
            ->orderBy('question_bank_id')
            ->orderBy('skill')
            ->orderBy('order')
            ->get();
    }

    public function allActiveByBank(int $bankId): Collection
    {
        return SkillPart::where('question_bank_id', $bankId)
            ->where('is_active', true)
            ->orderBy('skill')
            ->orderBy('order')
            ->get();
    }

    /**
     * @return array<int>
     */
    public function idsByBankAndSkill(int $bankId, string $skill): array
    {
        return SkillPart::where('question_bank_id', $bankId)
            ->where('skill', $skill)
            ->pluck('id')
            ->all();
    }

    /**
     * @return array<int>
     */
    public function orderedIdsByBankAndSkill(int $bankId, string $skill): array
    {
        return SkillPart::where('question_bank_id', $bankId)
            ->where('skill', $skill)
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
}
