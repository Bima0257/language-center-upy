<?php

namespace App\Modules\Exam\Services;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        private ExamRepositoryInterface $examRepo,
        private ExamSectionRepositoryInterface $sectionRepo,
        private QuestionRepositoryInterface $questionRepo,
        private QuestionBankRepositoryInterface $questionBanks,
        private SkillRepositoryInterface $skills,
        private SkillPartRepositoryInterface $skillParts,
    ) {}

    public function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->examRepo->paginateWithSections($perPage);
    }

    public function findWithRelations(int $id): ?Exam
    {
        return $this->examRepo->findWithRelations($id);
    }

    public function createWithSections(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = $this->examRepo->create($data);
            $bankId = $data['question_bank_id'];
            $bank = QuestionBank::find($bankId);

            $order = 1;
            foreach ($this->skills->allActiveOrdered() as $skill) {
                $hasApproved = $this->questionRepo->approvedBySkillForExamType(
                    $skill->code,
                    $exam->exam_type_id,
                    $bankId,
                )->isNotEmpty();

                if (! $hasApproved) {
                    continue;
                }

                $section = $this->sectionRepo->create([
                    'exam_id' => $exam->id,
                    'question_bank_id' => $bankId,
                    'skill_id' => $skill->id,
                    'title' => "{$skill->name} — {$bank->name}",
                    'order' => $order++,
                ]);

                foreach ($this->skillParts->allActiveByBank($bankId) as $part) {
                    if ($part->skill_id !== $skill->id) {
                        continue;
                    }

                    $this->sectionRepo->updateOrCreatePartPivot($section->id, $part->id, $part->order);
                }

                $this->attachApprovedQuestions($exam, $section, $skill);
            }

            return $exam;
        });
    }

    public function syncSections(Exam $exam): int
    {
        $bankId = $exam->sections->first()?->question_bank_id;

        if ($bankId === null) {
            return 0;
        }

        $bank = QuestionBank::find($bankId);
        $order = $exam->sections->max('order') ?? 0;
        $created = 0;

        foreach ($this->skills->allActiveOrdered() as $skill) {
            if ($exam->sections->contains('skill_id', $skill->id)) {
                continue;
            }

            $approved = $this->questionRepo->approvedBySkillForExamType(
                $skill->code,
                $exam->exam_type_id,
                $bankId,
            );

            if ($approved->isEmpty()) {
                continue;
            }

            $section = $this->sectionRepo->create([
                'exam_id' => $exam->id,
                'question_bank_id' => $bankId,
                'skill_id' => $skill->id,
                'title' => "{$skill->name} — {$bank->name}",
                'order' => ++$order,
            ]);

            foreach ($this->skillParts->allActiveByBank($bankId) as $part) {
                if ($part->skill_id === $skill->id) {
                    $this->sectionRepo->updateOrCreatePartPivot($section->id, $part->id, $part->order);
                }
            }

            $this->attachApprovedQuestions($exam, $section, $skill);
            $created++;
        }

        return $created;
    }

    public function update(Exam $exam, array $data): Exam
    {
        return $this->examRepo->update($exam, $data);
    }

    public function delete(Exam $exam): void
    {
        if ($this->examRepo->hasSchedules($exam)) {
            throw new \RuntimeException('Ujian tidak bisa dihapus karena sudah memiliki jadwal.');
        }

        $this->examRepo->delete($exam);
    }

    public function showData(Exam $exam): array
    {
        $exam = $this->examRepo->findWithRelations($exam->id);
        $sectionIds = $exam->sections->pluck('id');

        $attachable = [];
        foreach ($exam->sections as $section) {
            $bankId = $section->question_bank_id;

            if ($bankId === null) {
                $attachable[$section->id] = collect();

                continue;
            }

            $excludeIds = $this->sectionRepo->questionIdsInSection($section->id);
            $attachable[$section->id] = $this->questionRepo->approvedByBankAndSkillNotIn(
                $bankId,
                $section->skill->code,
                $excludeIds,
            );
        }

        return [
            'exam' => $exam,
            'skillOptions' => $this->skills->allActiveOrdered()->map(fn (Skill $skill) => ['value' => $skill->id, 'label' => $skill->name, 'code' => $skill->code])->toArray(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'questionBanks' => $this->questionBanks->allActiveOrdered(),
            'sectionQuestions' => $this->sectionRepo->questionsGroupedBySection($sectionIds),
            'sectionParts' => $this->sectionRepo->partsGroupedBySection($sectionIds),
            'attachableQuestions' => $attachable,
        ];
    }

    public function createData(): array
    {
        $banksBySkill = [];

        foreach ($this->skills->allActiveOrdered() as $skill) {
            $banksBySkill[] = [
                'skill_id' => $skill->id,
                'label' => $skill->name,
                'banks' => $this->questionRepo->banksWithApprovedBySkillAndExamType($skill->code, null),
            ];
        }

        return [
            'skillOptions' => $this->skills->allActiveOrdered()->map(fn (Skill $skill) => ['value' => $skill->id, 'label' => $skill->name, 'code' => $skill->code])->toArray(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'banksBySkill' => $banksBySkill,
        ];
    }

    public function activeQuestionBanks(): Collection
    {
        return $this->questionBanks->allActiveOrdered();
    }

    public function editData(Exam $exam): array
    {
        return [
            'exam' => $this->examRepo->findWithRelations($exam->id),
        ];
    }

    public function attachApprovedQuestions(Exam $exam, ExamSection $section, Skill $skill): int
    {
        $approved = $this->questionRepo->approvedBySkillForExamType($skill->code, $exam->exam_type_id, $section->question_bank_id);

        $number = 1;
        foreach ($approved as $question) {
            $this->sectionRepo->firstOrCreateQuestionPivot($section->id, $question->id, $number++);
        }

        return $approved->count();
    }
}
