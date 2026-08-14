<?php

namespace App\Modules\Session\Repositories\Contracts;

use App\Models\Answer;
use Illuminate\Support\Collection;

interface AnswerRepositoryInterface
{
    public function updateOrCreate(int $sessionId, int $questionId, array $data): Answer;

    public function getBySession(int $sessionId): Collection;

    public function getByQuestion(int $sessionId, int $questionId): ?Answer;
}
