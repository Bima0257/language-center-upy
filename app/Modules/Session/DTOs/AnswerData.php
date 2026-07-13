<?php

namespace App\Modules\Session\DTOs;

class AnswerData
{
    public function __construct(
        public readonly int $questionId,
        public readonly string $answer,
    ) {}
}
