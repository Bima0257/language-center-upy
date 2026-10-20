<?php

namespace App\Modules\DataInduk\Repositories\Contracts;

interface UrutanKodeRepositoryInterface
{
    public function pastikanRuang(string $ruang): void;

    public function nilaiBerikutnya(string $ruang): int;
}
