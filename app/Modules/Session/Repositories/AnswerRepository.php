<?php

namespace App\Modules\Session\Repositories;

use App\Models\Answer;
use App\Modules\Session\Repositories\Contracts\AnswerRepositoryInterface;
use Illuminate\Support\Collection;

class AnswerRepository implements AnswerRepositoryInterface
{
    public function updateOrCreate(int $sessionId, int $questionId, array $data): Answer
    {
        return Answer::updateOrCreate(
            ['exam_session_id' => $sessionId, 'question_id' => $questionId],
            $data,
        );
    }

    public function getBySession(int $sessionId): Collection
    {
        return Answer::where('exam_session_id', $sessionId)->get();
    }

    public function getByQuestion(int $sessionId, int $questionId): ?Answer
    {
        return Answer::where('exam_session_id', $sessionId)
            ->where('question_id', $questionId)
            ->first();
    }
}
