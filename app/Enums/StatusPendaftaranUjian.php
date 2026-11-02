<?php

namespace App\Enums;

/**
 * Status siklus hidup pendaftaran ujian.
 */
enum StatusPendaftaranUjian: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}
