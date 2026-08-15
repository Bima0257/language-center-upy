<?php

namespace App\Modules\MasterData\Repositories;

use App\Models\Certificate;
use App\Modules\MasterData\Repositories\Contracts\CertificateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CertificateRepository implements CertificateRepositoryInterface
{
    public function allWithRelations(): Collection
    {
        return Certificate::with([
            'examSession.user',
            'examSession.schedule.exam',
            'examSession.slot',
        ])->orderBy('issued_at', 'desc')->get();
    }

    public function create(array $data): Certificate
    {
        return Certificate::create($data);
    }

    public function delete(Certificate $certificate): void
    {
        $certificate->delete();
    }
}
