<?php

namespace App\Modules\Scoring\Actions;

use App\Enums\SkillCode;
use App\Models\ExamSession;
use App\Models\Question;
use App\Modules\Scoring\Services\ScoreConversionService;
use App\Modules\Session\Repositories\Contracts\AnswerRepositoryInterface;

class AutoScoreListening
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepo,
        private ScoreConversionService $conversionService,
    ) {}

    public function execute(int $sessionId): float
    {
        $answers = $this->answerRepo->getBySession($sessionId);
        $session = ExamSession::findOrFail($sessionId);

        $listeningQuestions = Question::where('skill', SkillCode::LISTENING)
            ->whereIn('id', $answers->pluck('question_id'))
            ->get();

        $correct = 0;
        $total = $listeningQuestions->count();

        foreach ($listeningQuestions as $question) {
            $answer = $answers->firstWhere('question_id', $question->id);
            if ($answer) {
                $isCorrect = $answer->answer_text === $question->correct_answer;
                $answer->update(['is_correct' => $isCorrect]);
                if ($isCorrect) {
                    $correct++;
                }
            }
        }

        $examTypeId = $session->schedule?->exam?->exam_type_id;

        return $this->conversionService->convert($examTypeId, 'listening', $correct, $total);
    }
}
