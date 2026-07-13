<?php

namespace App\Modules\Exam\Services;

use App\Models\Exam;
use App\Models\ExamSection;

class ExamSectionService
{
    public function create(Exam $exam, array $data): ExamSection
    {
        return $exam->sections()->create($data);
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
}
