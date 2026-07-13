<?php

namespace App\Modules\Security\Repositories;

use App\Models\ViolationLog;
use App\Modules\Security\Repositories\Contracts\ViolationRepositoryInterface;
use Illuminate\Support\Collection;

class ViolationRepository implements ViolationRepositoryInterface
{
    public function create(array $data): ViolationLog
    {
        return ViolationLog::create($data);
    }

    public function countBySession(int $sessionId): int
    {
        return ViolationLog::where('exam_session_id', $sessionId)->count();
    }

    public function getBySession(int $sessionId): Collection
    {
        return ViolationLog::where('exam_session_id', $sessionId)
            ->orderBy('created_at')
            ->get();
    }
}
