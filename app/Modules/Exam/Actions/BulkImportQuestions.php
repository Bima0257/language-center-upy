<?php

namespace App\Modules\Exam\Actions;

use App\Models\QuestionGroup;
use App\Modules\Exam\DTOs\BulkImportResult;

class BulkImportQuestions
{
    public function execute(QuestionGroup $group, array $questions): BulkImportResult
    {
        $imported = 0;

        foreach ($questions as $order => $question) {
            $group->questions()->create([
                'type' => $question['type'] ?? 'multiple_choice',
                'question_text' => $question['question_text'],
                'options' => isset($question['options']) ? json_encode($question['options']) : null,
                'correct_answer' => $question['correct_answer'] ?? null,
                'points' => $question['points'] ?? 1,
                'order' => $order + 1,
            ]);
            $imported++;
        }

        return new BulkImportResult($imported);
    }
}
