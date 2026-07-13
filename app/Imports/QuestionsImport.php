<?php

namespace App\Imports;

use App\Models\QuestionGroup;
use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToModel, WithHeadingRow
{
    private QuestionGroup $group;
    private int $order;

    public function __construct(QuestionGroup $group, int $startOrder = 0)
    {
        $this->group = $group;
        $this->order = $startOrder;
    }

    public function model(array $row)
    {
        $options = null;
        if (!empty($row['options'])) {
            $decoded = json_decode($row['options'], true);
            $options = $decoded ? json_encode($decoded) : null;
        }

        $this->order++;

        return new Question([
            'question_group_id' => $this->group->id,
            'type' => $row['type'] ?? 'multiple_choice',
            'question_text' => $row['question_text'] ?? '',
            'options' => $options,
            'correct_answer' => $row['correct_answer'] ?? null,
            'correct_answers' => isset($row['correct_answers']) ? json_decode($row['correct_answers'], true) : null,
            'points' => (int) ($row['points'] ?? 1),
            'order' => $this->order,
        ]);
    }
}
