<?php

namespace App\Modules\Scoring\Actions;

use App\Models\ExamSession;
use App\Models\Question;
use App\Modules\Scoring\Services\ScoreConversionService;
use App\Modules\Session\Repositories\Contracts\AnswerRepositoryInterface;

class AutoScoreReading
{
    public function __construct(
        private AnswerRepositoryInterface $answerRepo,
        private ScoreConversionService $conversionService,
    ) {}

    public function execute(int $sessionId): float
    {
        $answers = $this->answerRepo->getBySession($sessionId);
        $session = ExamSession::findOrFail($sessionId);

        $readingQuestions = Question::whereHas('skill', fn ($q) => $q->where('code', 'reading'))
            ->whereIn('id', $answers->pluck('question_id'))
            ->get();

        $correct = 0;
        $total = $readingQuestions->count();

        foreach ($readingQuestions as $question) {
            $answer = $answers->firstWhere('question_id', $question->id);
            if ($answer) {
                $isCorrect = $answer->answer_text === $question->correct_answer;
                $answer->update(['is_correct' => $isCorrect]);
                if ($isCorrect) {
                    $correct++;
                }
            }
        }

        $examTypeId = $session->slot?->schedule?->exam?->exam_type_id;

        return $this->conversionService->convert($examTypeId, 'reading', $correct, $total);
    }
}
