<?php

namespace App\Modules\Exam\Repositories\Contracts;

use App\Models\ExamSection;
use App\Models\ExamSectionQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

interface ExamSectionRepositoryInterface
{
    public function create(array $data): ExamSection;

    public function update(ExamSection $section, array $data): ExamSection;

    public function delete(ExamSection $section): void;

    public function questionsGroupedBySection(BaseCollection $sectionIds): BaseCollection;

    public function partsGroupedBySection(BaseCollection $sectionIds): BaseCollection;

    /**
     * @return Collection<int, ExamSectionQuestion>
     */
    public function pivotRowsBySectionIds(BaseCollection $sectionIds): Collection;

    public function firstOrCreateQuestionPivot(int $sectionId, int $questionId, int $order): void;

    public function createQuestionPivot(int $sectionId, int $questionId, int $order): void;

    public function deleteQuestionPivot(int $sectionId, int $questionId): void;

    /**
     * @return array<int>
     */
    public function questionIdsInSection(int $sectionId): array;

    public function nextQuestionOrder(int $sectionId): int;

    public function updateQuestionOrder(int $sectionId, int $questionId, int $order): void;

    public function updateOrCreatePartPivot(int $sectionId, int $skillPartId, int $order): void;

    /**
     * @return array<int>
     */
    public function partIdsInSectionOrdered(int $sectionId): array;

    /**
     * @return Collection<int, ExamSectionQuestion>
     */
    public function questionRowsWithQuestion(int $sectionId): Collection;

    public function updateQuestionPivotOrder(int $rowId, int $order): void;
}
