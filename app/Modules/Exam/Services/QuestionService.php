<?php

namespace App\Modules\Exam\Services;

use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuestionGroup;

class QuestionService
{
    public function createGroup(ExamSection $section, array $data): QuestionGroup
    {
        return $section->questionGroups()->create($data);
    }

    public function updateGroup(QuestionGroup $group, array $data): QuestionGroup
    {
        $group->update($data);
        return $group->fresh();
    }

    public function deleteGroup(QuestionGroup $group): void
    {
        $group->questions()->delete();
        $group->delete();
    }

    public function createQuestion(QuestionGroup $group, array $data): Question
    {
        return $group->questions()->create($data);
    }

    public function updateQuestion(Question $question, array $data): Question
    {
        $question->update($data);
        return $question->fresh();
    }

    public function deleteQuestion(Question $question): void
    {
        $question->delete();
    }
}
