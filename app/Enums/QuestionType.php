<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';

    public function isAutoScorable(): bool
    {
        return true;
    }
}
