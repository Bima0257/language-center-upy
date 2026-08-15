<?php

namespace App\Modules\MasterData\Repositories\Contracts;

use App\Models\Certificate;
use Illuminate\Database\Eloquent\Collection;

interface CertificateRepositoryInterface
{
    public function allWithRelations(): Collection;

    public function create(array $data): Certificate;

    public function delete(Certificate $certificate): void;
}
