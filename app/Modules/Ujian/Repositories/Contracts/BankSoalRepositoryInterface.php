<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\BankSoal;
use Illuminate\Database\Eloquent\Collection;

interface BankSoalRepositoryInterface
{
    public function semuaDenganProgramTesDanJumlah(?string $programTesId = null): Collection;

    public function semuaAktifBerurutan(): Collection;

    public function semuaAktifDenganProgramTesDanJumlah(?string $programTesId = null): Collection;

    public function semuaAktifDenganProgramTesBerurutan(?string $programTesId = null): Collection;

    public function find(string $id): ?BankSoal;

    public function digunakanDalamUjian(string $id): bool;

    public function adaAktif(string $id): bool;

    public function create(array $data): BankSoal;

    public function update(BankSoal $bankSoal, array $data): BankSoal;

    public function delete(BankSoal $bankSoal): void;
}
