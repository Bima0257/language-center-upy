<?php

namespace App\Modules\Exam\DTOs;

class QuestionData
{
    public function __construct(
        public readonly string $type,
        public readonly string $question_text,
        public readonly string $option_a,
        public readonly string $option_b,
        public readonly string $option_c,
        public readonly string $option_d,
        public readonly string $correct_answer,
        public readonly int $order = 0,
    ) {}
}
