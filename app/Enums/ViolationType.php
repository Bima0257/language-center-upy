<?php

namespace App\Enums;

enum ViolationType: string
{
    case TAB_SWITCH = 'tab_switch';
    case FULLSCREEN_EXIT = 'fullscreen_exit';
    case COPY_PASTE = 'copy_paste';
    case RIGHT_CLICK = 'right_click';
    case DEVTOOLS = 'devtools';
    case MULTIPLE_LOGIN = 'multiple_login';
    case HEARTBEAT_LOST = 'heartbeat_lost';
    case WINDOW_BLUR = 'window_blur';
    case PRINT_ATTEMPT = 'print_attempt';

    public function severity(): ViolationSeverity
    {
        return match ($this) {
            self::RIGHT_CLICK      => ViolationSeverity::WARNING,
            self::TAB_SWITCH       => ViolationSeverity::MINOR,
            self::FULLSCREEN_EXIT  => ViolationSeverity::MINOR,
            self::WINDOW_BLUR      => ViolationSeverity::MINOR,
            self::COPY_PASTE       => ViolationSeverity::MAJOR,
            self::DEVTOOLS         => ViolationSeverity::MAJOR,
            self::PRINT_ATTEMPT    => ViolationSeverity::MAJOR,
            self::HEARTBEAT_LOST   => ViolationSeverity::CRITICAL,
            self::MULTIPLE_LOGIN   => ViolationSeverity::CRITICAL,
        };
    }
}
