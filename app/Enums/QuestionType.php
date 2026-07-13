<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case MULTI_SELECT = 'multi_select';
    case ORDER = 'order';
    case MATCHING = 'matching';
    case FILL_BLANK = 'fill_blank';
    case ESSAY = 'essay';
    case SPEAKING = 'speaking';
    case TRUE_FALSE = 'true_false';
    case DICTATION = 'dictation';
    case ERROR_ID = 'error_id';

    public function isAutoScorable(): bool
    {
        return match ($this) {
            self::MULTIPLE_CHOICE,
            self::TRUE_FALSE,
            self::ERROR_ID,
            self::FILL_BLANK     => true,

            self::MULTI_SELECT,
            self::MATCHING,
            self::ORDER          => true, // partial credit

            self::DICTATION      => false, // fuzzy match, but manual preferred
            self::ESSAY,
            self::SPEAKING      => false,
        };
    }
}
