<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\PassageRepositoryInterface;
use App\Modules\Exam\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\Schedule\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use App\Modules\Users\Repositories\Contracts\UserRepositoryInterface;

class DashboardService
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessions,
        private ScheduleRepositoryInterface $schedules,
        private ExamRepositoryInterface $exams,
        private QuestionRepositoryInterface $questions,
        private PassageRepositoryInterface $passages,
        private UserRepositoryInterface $users,
    ) {}

    public function dataFor(User $user): array
    {
        $data = [];

        if ($user->hasRole('student')) {
            $data['recentSessions'] = $this->sessions->recentForUser($user->id);
            $data['availableExamsCount'] = $this->schedules->countAvailableNow();
        }

        if ($user->hasRole('admin') || $user->hasRole('superadmin')) {
            $data['totalUsers'] = $this->users->count();
            $data['totalExams'] = $this->exams->count();
            $data['activeSessionsCount'] = $this->sessions->countActive();
            $data['flaggedSessionsCount'] = $this->sessions->countFlaggedPendingReview();
            $data['pendingReviewCount'] = $this->questions->countDraft();
            $data['pendingQuestions'] = $this->questions->pendingDrafts();
        }

        if ($user->hasRole('instructor')) {
            $data['totalQuestions'] = $this->questions->count();
            $data['totalPassages'] = $this->passages->count();

            $data['questionsBySkill'] = $this->questions->countBySkill();

            $data['questionsByStatus'] = $this->questions->countByStatus();

            $data['questionsByBank'] = $this->questions->countByBank();
        }

        return $data;
    }
}
