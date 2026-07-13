<?php

namespace App\Modules\Session\Actions;

use App\Models\ExamSession;
use App\Models\FlaggedQuestion;

class ToggleFlaggedQuestion
{
    public function execute(int $sessionId, int $questionId): void
    {
        $flagged = FlaggedQuestion::where('exam_session_id', $sessionId)
            ->where('question_id', $questionId)
            ->first();

        if ($flagged) {
            $flagged->delete();
        } else {
            FlaggedQuestion::create([
                'exam_session_id' => $sessionId,
                'question_id' => $questionId,
            ]);
        }
    }
}
