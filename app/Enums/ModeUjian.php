<?php

namespace App\Enums;

/**
 * Mode pelaksanaan ujian.
 */
enum ModeUjian: string
{
    case TRYOUT = 'tryout';
    case OFFICIAL = 'official';
}
