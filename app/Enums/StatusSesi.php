<?php

namespace App\Enums;

/**
 * Status siklus hidup sesi ujian.
 */
enum StatusSesi: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case TERMINATED = 'terminated';
}
