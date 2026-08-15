<?php

namespace App\Modules\Exam\Repositories;

use App\Models\ExamSection;
use App\Models\ExamSectionPart;
use App\Models\ExamSectionQuestion;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class ExamSectionRepository implements ExamSectionRepositoryInterface
{
    public function create(array $data): ExamSection
    {
        return ExamSection::create($data);
    }

    public function update(ExamSection $section, array $data): ExamSection
    {
        $section->update($data);

        return $section->fresh();
    }

    public function delete(ExamSection $section): void
    {
        $section->delete();
    }

    public function questionsGroupedBySection(BaseCollection $sectionIds): BaseCollection
    {
        return ExamSectionQuestion::with(['question.passage', 'question.skillPart'])
            ->whereIn('exam_section_id', $sectionIds)
            ->get()
            ->groupBy('exam_section_id');
    }

    public function partsGroupedBySection(BaseCollection $sectionIds): BaseCollection
    {
        return ExamSectionPart::whereIn('exam_section_id', $sectionIds)
            ->get()
            ->groupBy('exam_section_id');
    }

    /**
     * @return Collection<int, ExamSectionQuestion>
     */
    public function pivotRowsBySectionIds(BaseCollection $sectionIds): Collection
    {
        return ExamSectionQuestion::whereIn('exam_section_id', $sectionIds)->get();
    }

    public function firstOrCreateQuestionPivot(int $sectionId, int $questionId, int $order): void
    {
        ExamSectionQuestion::firstOrCreate(
            ['exam_section_id' => $sectionId, 'question_id' => $questionId],
            ['order' => $order],
        );
    }

    public function createQuestionPivot(int $sectionId, int $questionId, int $order): void
    {
        ExamSectionQuestion::create([
            'exam_section_id' => $sectionId,
            'question_id' => $questionId,
            'order' => $order,
        ]);
    }

    public function deleteQuestionPivot(int $sectionId, int $questionId): void
    {
        ExamSectionQuestion::where('exam_section_id', $sectionId)
            ->where('question_id', $questionId)
            ->delete();
    }

    /**
     * @return array<int>
     */
    public function questionIdsInSection(int $sectionId): array
    {
        return ExamSectionQuestion::where('exam_section_id', $sectionId)->pluck('question_id')->all();
    }

    public function nextQuestionOrder(int $sectionId): int
    {
        return (ExamSectionQuestion::where('exam_section_id', $sectionId)->max('order') ?? 0) + 1;
    }

    public function updateQuestionOrder(int $sectionId, int $questionId, int $order): void
    {
        ExamSectionQuestion::where('exam_section_id', $sectionId)
            ->where('question_id', $questionId)
            ->update(['order' => $order]);
    }

    public function updateOrCreatePartPivot(int $sectionId, int $skillPartId, int $order): void
    {
        ExamSectionPart::updateOrCreate(
            ['exam_section_id' => $sectionId, 'skill_part_id' => $skillPartId],
            ['order' => $order],
        );
    }

    /**
     * @return array<int>
     */
    public function partIdsInSectionOrdered(int $sectionId): array
    {
        return ExamSectionPart::where('exam_section_id', $sectionId)
            ->orderBy('order')
            ->pluck('skill_part_id')
            ->all();
    }

    /**
     * @return Collection<int, ExamSectionQuestion>
     */
    public function questionRowsWithQuestion(int $sectionId): Collection
    {
        return ExamSectionQuestion::with('question')
            ->where('exam_section_id', $sectionId)
            ->get();
    }

    public function updateQuestionPivotOrder(int $rowId, int $order): void
    {
        ExamSectionQuestion::whereKey($rowId)->update(['order' => $order]);
    }
}
