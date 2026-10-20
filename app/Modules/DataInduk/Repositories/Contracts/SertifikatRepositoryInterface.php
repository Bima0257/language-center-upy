<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

use App\Models\Sertifikat;
use Illuminate\Database\Eloquent\Collection;

interface SertifikatRepositoryInterface
{
    public function semuaDenganRelasi(): Collection;

    public function create(array $data): Sertifikat;

    public function delete(Sertifikat $sertifikat): void;
}
