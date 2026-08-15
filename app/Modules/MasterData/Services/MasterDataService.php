<?php

namespace App\Modules\MasterData\Services;

use App\Models\Certificate;
use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\ScoreInterpretation;
use App\Models\SkillPart;
use App\Modules\MasterData\Repositories\Contracts\CertificateRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\FacultyRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\ScoreInterpretationRepositoryInterface;
use App\Modules\MasterData\Repositories\Contracts\SkillPartRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mews\Purifier\Facades\Purifier;

class MasterDataService
{
    public function __construct(
        private ExamTypeRepositoryInterface $examTypes,
        private SkillPartRepositoryInterface $skillParts,
        private FacultyRepositoryInterface $faculties,
        private DepartmentRepositoryInterface $departments,
        private ScoreInterpretationRepositoryInterface $scoreInterpretations,
        private CertificateRepositoryInterface $certificates,
    ) {}

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
        $this->examTypes->delete($examType);
    }

    public function skillPartsIndexData(): array
    {
        return [
            'parts' => $this->skillParts->allWithQuestionCounts(),
        ];
    }

    public function createSkillPart(array $data): void
    {
        $this->skillParts->create($this->sanitizeOptionalText($data));
    }

    public function updateSkillPart(SkillPart $skillPart, array $data): void
    {
        $this->skillParts->update($skillPart, $this->sanitizeOptionalText($data));
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
        return $this->skillParts->allActiveOrderedBySkill();
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
