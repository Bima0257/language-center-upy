<?php

namespace App\Modules\Ujian\Repositories\Contracts;

use App\Models\BankSoal;
use Illuminate\Database\Eloquent\Collection;

interface BankSoalRepositoryInterface
{
    public function semuaDenganJenisTesDanJumlah(?string $jenisTesId = null): Collection;

    public function semuaAktifBerurutan(): Collection;

    public function semuaAktifDenganJenisTesDanJumlah(?string $jenisTesId = null): Collection;

    public function semuaAktifDenganJenisTesBerurutan(?string $jenisTesId = null): Collection;

    public function find(string $id): ?BankSoal;

    public function adaAktif(string $id): bool;

    public function create(array $data): BankSoal;

    public function update(BankSoal $bankSoal, array $data): BankSoal;

    public function delete(BankSoal $bankSoal): void;
}
