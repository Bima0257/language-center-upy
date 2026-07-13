<?php

namespace App\Modules\Security\Repositories\Contracts;

use App\Models\ViolationLog;

interface ViolationRepositoryInterface
{
    public function create(array $data): ViolationLog;
    public function countBySession(int $sessionId): int;
    public function getBySession(int $sessionId): \Illuminate\Support\Collection;
}
