<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToModel, WithHeadingRow
{
    private int $order;

    public function __construct(int $startOrder = 0)
    {
        $this->order = $startOrder;
    }

    public function model(array $row)
    {
        $this->order++;

        return new Question([
            'question_bank_id' => $row['question_bank_id'] ?? null,
            'skill_id' => $row['skill_id'] ?? null,
            'passage_id' => $row['passage_id'] ?? null,
            'type' => 'multiple_choice',
            'question_text' => $row['question_text'] ?? '',
            'option_a' => $row['option_a'] ?? '',
            'option_b' => $row['option_b'] ?? '',
            'option_c' => $row['option_c'] ?? '',
            'option_d' => $row['option_d'] ?? '',
            'correct_answer' => $row['correct_answer'] ?? null,
            'audio_url' => $row['audio_url'] ?? null,
            'image_url' => $row['image_url'] ?? null,
            'order' => $this->order,
        ]);
    }
}
