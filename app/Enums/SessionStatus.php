<?php

namespace App\Enums;

enum SessionStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case TERMINATED = 'terminated';
    case REVIEWED = 'reviewed';
}
