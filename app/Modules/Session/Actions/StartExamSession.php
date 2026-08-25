<?php

namespace App\Modules\Session\Actions;

use App\Enums\SessionStatus;
use App\Modules\Schedule\Repositories\Contracts\SlotRepositoryInterface;
use App\Modules\Security\Services\SecurityService;
use App\Modules\Session\DTOs\SessionStartedResult;
use App\Modules\Session\Repositories\Contracts\ExamSessionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class StartExamSession
{
    public function __construct(
        private ExamSessionRepositoryInterface $sessionRepo,
        private SlotRepositoryInterface $slotRepo,
        private SecurityService $security,
    ) {}

    public function execute(
        int $userId,
        int $slotId,
        ?string $deviceType = null,
        ?string $deviceUserAgent = null,
        ?UploadedFile $selfie = null,
    ): SessionStartedResult {
        $slot = $this->slotRepo->findOrFail($slotId);

        if (! $slot->is_active) {
            throw new \RuntimeException('Sesi ujian tidak aktif.');
        }

        if (! $slot->schedule->isActive()) {
            throw new \RuntimeException('Jadwal ujian ini sudah tidak berlaku.');
        }

        if (now()->toDateString() !== $slot->date->toDateString()) {
            throw new \RuntimeException('Sesi ujian tidak tersedia pada hari ini. Jadwal: '.$slot->date->locale('id')->isoFormat('dddd, D MMMM Y').'.');
        }

        $now = now()->format('H:i');
        $start = substr((string) $slot->start_time, 0, 5);
        $lateDeadline = Carbon::createFromFormat('H:i', $start)
            ->addMinutes($slot->late_tolerance_minutes)
            ->format('H:i');

        if ($now < $start) {
            throw new \RuntimeException('Sesi ujian belum dibuka. Mulai pukul '.$start.' WIB.');
        }

        if ($now > $lateDeadline) {
            throw new \RuntimeException('Waktu masuk ujian telah berakhir. Batas keterlambatan pukul '.$lateDeadline.' WIB.');
        }

        if ($slot->sessions()->count() >= $slot->max_participants) {
            throw new \RuntimeException('Kuota sesi ujian sudah penuh ('.$slot->max_participants.' peserta).');
        }

        if ($this->sessionRepo->hasSessionForSchedule($userId, $slot->exam_schedule_id)) {
            throw new \RuntimeException('Anda sudah mengikuti ujian pada periode ini. Satu percobaan per periode.');
        }

        $activeSession = $this->sessionRepo->findActiveSession($userId);
        if ($activeSession !== null) {
            return new SessionStartedResult(
                sessionId: $activeSession->id,
                firstSectionId: $activeSession->current_section_id,
            );
        }

        $firstSection = $slot->schedule->exam->sections->first();

        $selfiePath = null;
        if ($selfie !== null) {
            $selfiePath = $selfie->store('selfies', 'public');
        }

        $session = $this->sessionRepo->create([
            'exam_schedule_slot_id' => $slot->id,
            'user_id' => $userId,
            'status' => SessionStatus::IN_PROGRESS,
            'started_at' => now(),
            'current_section_id' => $firstSection?->id,
            'device_type' => $deviceType,
            'device_user_agent' => $deviceUserAgent,
            'selfie_path' => $selfiePath,
            'selfie_taken_at' => $selfie !== null ? now() : null,
        ]);

        $this->security->logSystemEvent($session->id, 'exam_started');

        return new SessionStartedResult(
            sessionId: $session->id,
            firstSectionId: $firstSection?->id,
        );
    }
}
