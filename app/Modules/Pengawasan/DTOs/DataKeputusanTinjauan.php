<?php

namespace App\Modules\Pengawasan\DTOs;

class DataKeputusanTinjauan
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $catatan,
        public readonly ?string $peninjauId,
    ) {}
}
