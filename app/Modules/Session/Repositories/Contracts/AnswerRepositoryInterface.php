<?php

namespace App\Modules\Session\Repositories\Contracts;

use App\Models\Answer;

interface AnswerRepositoryInterface
{
    public function updateOrCreate(int $sessionId, int $questionId, array $data): Answer;
    public function getBySession(int $sessionId): \Illuminate\Support\Collection;
    public function getByQuestion(int $sessionId, int $questionId): ?Answer;
}
