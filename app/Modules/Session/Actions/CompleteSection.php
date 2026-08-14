<?php

namespace App\Modules\Session\Actions;

use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class CompleteSection
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(ExamSession $session): void
    {
        $currentSection = $session->currentSection;
        if (! $currentSection) {
            return;
        }

        $nextSection = ExamSection::query()
            ->where('exam_id', $currentSection->exam_id)
            ->where('order', '>', $currentSection->order)
            ->orderBy('order')
            ->first();

        $this->sessionRepo->update($session->id, [
            'current_section_id' => $nextSection?->id,
        ]);
    }
}
