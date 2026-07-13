<?php

namespace App\Modules\Exam\DTOs;

class QuestionData
{
    public function __construct(
        public readonly string $type,
        public readonly string $question_text,
        public readonly ?array $options = null,
        public readonly ?string $correct_answer = null,
        public readonly int $points = 1,
        public readonly int $order = 0,
    ) {}
}
