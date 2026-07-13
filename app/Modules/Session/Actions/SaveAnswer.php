<?php

namespace App\Modules\Session\Actions;

use App\Modules\Session\Repositories\Contracts\AnswerRepositoryInterface;

class SaveAnswer
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepo,
    ) {}

    public function execute(int $sessionId, int $questionId, ?string $answer, ?array $answerJson = null): void
    {
        $this->answerRepo->updateOrCreate($sessionId, $questionId, [
            'answer_text' => $answer,
            'answer_json' => $answerJson,
        ]);
    }
}
