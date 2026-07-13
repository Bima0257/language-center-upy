<?php

namespace App\Modules\Exam\DTOs;

class ExamData
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $type = 'tryout',
        public readonly int $duration_minutes = 160,
        public readonly bool $is_active = true,
    ) {}
}
