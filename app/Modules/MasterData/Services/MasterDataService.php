<?php

namespace App\Modules\MasterData\Services;

use App\Models\Certificate;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\ScoreInterpretation;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Modules\Exam\Repositories\Contracts\QuestionBankRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\CertificateRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\FacultyRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ScoreInterpretationRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mews\Purifier\Facades\Purifier;

class MasterDataService
{
    public function __construct(
        private ExamTypeRepositoryInterface $examTypes,
        private SkillRepositoryInterface $skills,
        private SkillPartRepositoryInterface $skillParts,
        private FacultyRepositoryInterface $faculties,
        private DepartmentRepositoryInterface $departments,
        private ScoreInterpretationRepositoryInterface $scoreInterpretations,
        private CertificateRepositoryInterface $certificates,
        private QuestionBankRepositoryInterface $questionBanks,
    ) {}

    // ===== Skills =====
    public function skillsIndexData(): array
    {
        return [
            'skills' => $this->skills->allOrdered(),
        ];
    }

    public function createSkill(array $data): void
    {
        $data = $this->sanitizeOptionalText($data);
        $data['order'] = $data['order'] ?? $this->skills->nextOrder();

        $this->skills->create($data);
    }

    public function updateSkill(Skill $skill, array $data): void
    {
        $data = $this->sanitizeOptionalText($data);

        $this->skills->update($skill, $data);
    }

    public function deleteSkill(Skill $skill): void
    {
        if ($skill->skillParts()->exists() || $skill->questions()->exists() || $skill->examSections()->exists()) {
            throw new \RuntimeException('Skill tidak bisa dihapus karena masih digunakan di part soal, soal, atau section ujian.');
        }

        $this->skills->delete($skill);
    }

    /**
     * Terima daftar id skill dalam urutan baru; order dihitung ulang berdasarkan posisi.
     *
     * @param  array<int>  $ids
     */
    public function reorderSkills(array $ids): void
    {
        $skills = $this->skills->findMany($ids);

        foreach ($ids as $index => $id) {
            $skill = $skills->firstWhere('id', $id);
            if (! $skill) {
                continue;
            }

            $newOrder = $index + 1;
            if ($skill->order !== $newOrder) {
                $this->skills->update($skill, ['order' => $newOrder]);
            }
        }
    }

    // ===== Exam Types =====
    public function examTypesIndexData(): array
    {
        return [
            'examTypes' => $this->examTypes->allWithQuestionBankCounts(),
        ];
    }

    public function createExamType(array $data): void
    {
        $this->examTypes->create($this->sanitizeOptionalText($data));
    }

    public function updateExamType(ExamType $examType, array $data): void
    {
        $this->examTypes->update($examType, $this->sanitizeOptionalText($data));
    }

    public function deleteExamType(ExamType $examType): void
    {
        if ($examType->questionBanks()->exists() || $examType->exams()->exists()) {
            throw new \RuntimeException('Jenis tes tidak bisa dihapus karena masih dipakai bank soal atau ujian.');
        }

        $this->examTypes->delete($examType);
    }

    public function skillPartsIndexData(?int $bankId = null): array
    {
        $parts = $this->skillParts->allWithQuestionCounts();

        return [
            'parts' => $bankId !== null ? $parts->where('question_bank_id', $bankId)->values() : $parts,
            'questionBanks' => $this->questionBanks->allActiveOrdered(),
            'skillOptions' => $this->skills->allActiveOrdered()->map(fn (Skill $skill) => ['value' => $skill->id, 'label' => $skill->name])->toArray(),
        ];
    }

    public function createSkillPart(array $data): void
    {
        $data = $this->sanitizeOptionalText($data);
        $data['order'] = $this->skillParts->nextOrder((int) $data['question_bank_id'], (int) $data['skill_id']);

        $this->skillParts->create($data);
    }

    public function updateSkillPart(SkillPart $skillPart, array $data): void
    {
        $data = $this->sanitizeOptionalText($data);

        // Pindah bank/skill → urutan otomatis di akhir grup baru (hindari tabrakan urutan)
        if ((int) ($data['question_bank_id'] ?? $skillPart->question_bank_id) !== $skillPart->question_bank_id
            || (int) ($data['skill_id'] ?? $skillPart->skill_id) !== $skillPart->skill_id) {
            $data['order'] = $this->skillParts->nextOrder((int) $data['question_bank_id'], (int) $data['skill_id']);
        }

        $this->skillParts->update($skillPart, $data);
    }

    /**
     * Terima daftar id part dalam urutan baru; order dihitung ulang per (bank, skill).
     *
     * @param  array<int>  $ids
     */
    public function reorderSkillParts(array $ids): void
    {
        $parts = $this->skillParts->findMany($ids);
        $rank = [];

        foreach ($ids as $index => $id) {
            $part = $parts->firstWhere('id', $id);
            if (! $part) {
                continue;
            }

            $groupKey = $part->question_bank_id.'|'.$part->skill_id;
            $rank[$groupKey] = ($rank[$groupKey] ?? 0) + 1;

            if ($part->order !== $rank[$groupKey]) {
                $this->skillParts->update($part, ['order' => $rank[$groupKey]]);
            }
        }
    }

    public function deleteSkillPart(SkillPart $skillPart): void
    {
        $this->skillParts->delete($skillPart);
    }

    public function facultiesIndexData(): array
    {
        return [
            'faculties' => $this->faculties->allWithDepartmentCounts(),
        ];
    }

    public function createFaculty(array $data): void
    {
        $this->faculties->create($data);
    }

    public function updateFaculty(Faculty $faculty, array $data): void
    {
        $this->faculties->update($faculty, $data);
    }

    public function deleteFaculty(Faculty $faculty): void
    {
        $this->faculties->delete($faculty);
    }

    public function departmentsIndexData(): array
    {
        return [
            'departments' => $this->departments->allWithFaculty(),
            'faculties' => $this->faculties->allActiveOrdered(),
        ];
    }

    public function createDepartment(array $data): void
    {
        $this->departments->create($data);
    }

    public function updateDepartment(Department $department, array $data): void
    {
        $this->departments->update($department, $data);
    }

    public function deleteDepartment(Department $department): void
    {
        $this->departments->delete($department);
    }

    public function scoreInterpretationsIndexData(): array
    {
        return [
            'examTypes' => $this->examTypes->allOrdered(),
            'interpretations' => $this->scoreInterpretations->allWithExamType(),
        ];
    }

    public function createScoreInterpretation(array $data): void
    {
        $this->scoreInterpretations->create($data);
    }

    public function updateScoreInterpretation(ScoreInterpretation $interpretation, array $data): void
    {
        $this->scoreInterpretations->update($interpretation, $data);
    }

    public function deleteScoreInterpretation(ScoreInterpretation $interpretation): void
    {
        $this->scoreInterpretations->delete($interpretation);
    }

    public function certificatesIndexData(): array
    {
        return [
            'certificates' => $this->certificates->allWithRelations(),
        ];
    }

    public function createCertificate(array $data): void
    {
        $this->certificates->create($data);
    }

    public function deleteCertificate(Certificate $certificate): void
    {
        $this->certificates->delete($certificate);
    }

    public function activeFaculties(): Collection
    {
        return $this->faculties->allActiveOrdered();
    }

    public function activeDepartments(): Collection
    {
        return $this->departments->allActiveOrdered();
    }

    public function activeSkillParts(): Collection
    {
        return $this->skillParts->allActiveOrdered();
    }

    public function activeSkills(): Collection
    {
        return $this->skills->allActiveOrdered();
    }

    public function activeExamTypes(): Collection
    {
        return $this->examTypes->allActiveOrdered();
    }

    private function sanitizeOptionalText(array $data): array
    {
        foreach (['description', 'directions'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = Purifier::clean($data[$field]);
            }
        }

        return $data;
    }
}
