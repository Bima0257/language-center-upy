<?php

namespace App\Enums;

/**
 * Status siklus hidup skema penilaian.
 */
enum StatusSkemaPenilaian: string
{
    case DRAFT = 'draft';
    case AKTIF = 'aktif';
    case ARSIP = 'arsip';
}
