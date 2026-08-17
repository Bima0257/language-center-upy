<?php

namespace App\Modules\Session\Services;

use App\Models\ExamSession;
use App\Modules\Exam\Repositories\Contracts\ExamSectionRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ExamRuntimeService
{
    public function __construct(
        private ExamSectionRepositoryInterface $sections,
        private QuestionRepositoryInterface $questions,
    ) {}

    public function questionsForSession(ExamSession $session): Collection
    {
        $exam = $session->slot?->schedule?->exam;
        $sections = $exam !== null ? $exam->sections : new Collection;

        $pivotRows = $this->sections->pivotRowsBySectionIds($sections->pluck('id'));
        $pivotQuestionIds = $pivotRows->pluck('question_id');

        $questions = new Collection;

        if ($pivotRows->isNotEmpty()) {
            $questions = $this->questions->findByIdsWithPassage($pivotQuestionIds->all())
                ->each(function ($q) use ($pivotRows) {
                    $row = $pivotRows->firstWhere('question_id', $q->id);
                    $q->order = $row !== null ? $row->order : $q->order;
                })
                ->sortBy('order')
                ->values();
        }

        $emptySections = $sections->filter(fn ($s) => ! $pivotRows->where('exam_section_id', $s->id)->count());

        if ($emptySections->isNotEmpty()) {
            $skills = $emptySections->pluck('skill')->map(fn ($skill) => $skill->value)->unique()->all();
            $bankIds = $emptySections->pluck('question_bank_id')->filter()->unique()->all();

            $fallback = $this->questions->fallbackApprovedBySkills(
                $skills,
                $exam?->exam_type_id,
                count($bankIds) === 1 ? $bankIds[0] : null,
                $pivotQuestionIds->all(),
            );

            $questions = $questions->merge($fallback);
        }

        return $questions;
    }
}
