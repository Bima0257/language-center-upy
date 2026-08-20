<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function data(): array
    {
        return [
            'usersByRole' => $this->usersByRole(),
            'sessionsByMonth' => $this->sessionsByMonth(),
            'scoresBySkill' => $this->scoresBySkill(),
            'passFailRate' => $this->passFailRate(),
            'topStudents' => $this->topStudents(),
            'participationByFaculty' => $this->participationByFaculty(),
        ];
    }

    private function usersByRole(): array
    {
        return User::join('model_has_roles', 'users.id', '=', 'model_has_users')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as label', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get()
            ->toArray();
    }

    private function sessionsByMonth(): array
    {
        return ExamSession::where('status', 'submitted')
            ->select(DB::raw("date_format(created_at, '%Y-%m') as label"), DB::raw('count(*) as count'))
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->toArray();
    }

    private function scoresBySkill(): array
    {
        $reading = ExamSession::where('status', 'submitted')
            ->whereNotNull('score_reading')
            ->avg('score_reading');

        $listening = ExamSession::where('status', 'submitted')
            ->whereNotNull('score_listening')
            ->avg('score_listening');

        return [
            ['label' => 'Reading', 'count' => round($reading ?? 0, 1)],
            ['label' => 'Listening', 'count' => round($listening ?? 0, 1)],
        ];
    }

    private function passFailRate(): array
    {
        $submitted = ExamSession::where('status', 'submitted')->count();
        $passing = config('settings.passing_score', 300);
        $passed = ExamSession::where('status', 'submitted')
            ->where('score_total', '>=', $passing)
            ->count();

        return [
            ['label' => 'Lulus', 'count' => $passed],
            ['label' => 'Tidak Lulus', 'count' => max(0, $submitted - $passed)],
        ];
    }

    private function topStudents(): array
    {
        return ExamSession::where('status', 'submitted')
            ->whereNotNull('score_total')
            ->with('user:id,name,email')
            ->orderByDesc('score_total')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'name' => optional($s->user)->name ?? '-',
                'score' => $s->score_total,
                'date' => $s->created_at->format('d M Y'),
            ])
            ->toArray();
    }

    private function participationByFaculty(): array
    {
        return User::whereHas('studentProfile')
            ->join('student_profiles', 'users.id', '=', 'student_profiles.user_id')
            ->join('faculties', 'student_profiles.faculty_id', '=', 'faculties.id')
            ->join('exam_sessions', 'users.id', '=', 'exam_sessions.user_id')
            ->select('faculties.name as label', DB::raw('count(distinct users.id) as count'))
            ->groupBy('faculties.name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
