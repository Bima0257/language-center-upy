<?php

namespace App\Modules\Exam\Services;

use App\Enums\SkillCode;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExamService
{
    public function __construct(
        private ExamRepositoryInterface $examRepo,
        private ExamSectionRepositoryInterface $sectionRepo,
        private QuestionRepositoryInterface $questionRepo,
        private QuestionBankRepositoryInterface $questionBanks,
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

            $order = 1;
            foreach (SkillCode::cases() as $skill) {
                $banks = $this->questionRepo->banksWithApprovedBySkillAndExamType($skill->value, $exam->exam_type_id);

                foreach ($banks as $bank) {
                    $section = $this->sectionRepo->create([
                        'exam_id' => $exam->id,
                        'question_bank_id' => $bank->id,
                        'skill' => $skill,
                        'title' => "{$skill->label()} — {$bank->name}",
                        'order' => $order++,
                    ]);

                    foreach ($this->skillParts->allActiveByBank($bank->id) as $part) {
                        if ($part->skill !== $skill) {
                            continue;
                        }

                        $this->sectionRepo->updateOrCreatePartPivot($section->id, $part->id, $part->order);
                    }

                    $this->attachApprovedQuestions($exam, $section, $skill);
                }
            }

            return $exam;
        });
    }

    public function update(Exam $exam, array $data): Exam
    {
        return $this->examRepo->update($exam, $data);
    }

    public function delete(Exam $exam): void
    {
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
                $section->skill->value,
                $excludeIds,
            );
        }

        return [
            'exam' => $exam,
            'skillOptions' => SkillCode::options(),
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

        foreach (SkillCode::cases() as $skill) {
            $banksBySkill[] = [
                'skill' => $skill->value,
                'label' => $skill->label(),
                'banks' => $this->questionRepo->banksWithApprovedBySkillAndExamType($skill->value, null),
            ];
        }

        return [
            'skillOptions' => SkillCode::options(),
            'parts' => $this->skillParts->allActiveOrdered(),
            'banksBySkill' => $banksBySkill,
        ];
    }

    public function editData(Exam $exam): array
    {
        return [
            'exam' => $this->examRepo->findWithRelations($exam->id),
        ];
    }

    public function attachApprovedQuestions(Exam $exam, ExamSection $section, SkillCode $skill): int
    {
        $approved = $this->questionRepo->approvedBySkillForExamType($skill->value, $exam->exam_type_id, $section->question_bank_id);

        $number = 1;
        foreach ($approved as $question) {
            $this->sectionRepo->firstOrCreateQuestionPivot($section->id, $question->id, $number++);
        }

        return $approved->count();
    }
}
