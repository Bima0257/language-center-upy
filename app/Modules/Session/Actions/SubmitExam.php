<?php

namespace App\Modules\Session\Actions;

use App\Enums\SessionStatus;
use App\Modules\Scoring\Actions\AutoScoreListening;
use App\Modules\Scoring\Actions\AutoScoreReading;
use App\Modules\Scoring\Actions\CalculateTotalScore;
use App\Modules\Session\DTOs\SubmitResult;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;

class SubmitExam
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
        private AutoScoreReading $autoScoreReading,
        private AutoScoreListening $autoScoreListening,
        private CalculateTotalScore $calculateTotal,
    ) {}

    public function execute(int $sessionId): SubmitResult
    {
        $session = $this->sessionRepo->findWithAnswers($sessionId);

        if (! $session || $session->status === SessionStatus::SUBMITTED) {
            throw new \RuntimeException('Ujian sudah disubmit sebelumnya.');
        }

        $scoreReading = $this->autoScoreReading->execute($sessionId);
        $scoreListening = $this->autoScoreListening->execute($sessionId);

        $total = $this->calculateTotal->execute(
            $scoreReading,
            $scoreListening,
            0,
            0,
        );

        $updates = [
            'status' => SessionStatus::SUBMITTED,
            'submitted_at' => now(),
            'score_reading' => $scoreReading,
            'score_listening' => $scoreListening,
            'score_total' => $total,
        ];

        $maxStrikes = $session->getMaxStrikes();

        if ($session->violation_strikes >= $maxStrikes) {
            $updates['is_flagged'] = true;
            $updates['flag_reason'] = "Pelanggaran mencapai {$maxStrikes} strike.";
        }

        $this->sessionRepo->update($sessionId, $updates);

        activity()
            ->performedOn($session)
            ->withProperties([
                'score_reading' => $scoreReading,
                'score_listening' => $scoreListening,
                'score_total' => $total,
            ])
            ->log('exam_submitted');

        return new SubmitResult(
            sessionId: $sessionId,
            scoreReading: $scoreReading,
            scoreListening: $scoreListening,
            scoreTotal: $total,
        );
    }
}
