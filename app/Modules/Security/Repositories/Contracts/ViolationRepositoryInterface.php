<?php

namespace App\Modules\Security\Repositories\Contracts;

use App\Models\ViolationLog;
use Illuminate\Support\Collection;

interface ViolationRepositoryInterface
{
    public function create(array $data): ViolationLog;

    public function countBySession(int $sessionId): int;

    public function getBySession(int $sessionId): Collection;
}
