<?php

namespace App\Modules\Exam\DTOs;

class BulkImportResult
{
    public function __construct(
        public readonly int $imported,
    ) {}
}
