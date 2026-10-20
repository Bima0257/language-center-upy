<?php

namespace App\Enums;

/**
 * Jenis kejadian yang dicatat sebagai pelanggaran ujian.
 */
enum JenisPelanggaran: string
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

    /**
     * Tingkat bawaan untuk jenis pelanggaran ini.
     */
    public function tingkat(): TingkatPelanggaran
    {
        return match ($this) {
            self::RIGHT_CLICK => TingkatPelanggaran::WARNING,
            self::TAB_SWITCH => TingkatPelanggaran::MINOR,
            self::FULLSCREEN_EXIT => TingkatPelanggaran::MINOR,
            self::WINDOW_BLUR => TingkatPelanggaran::MINOR,
            self::COPY_PASTE => TingkatPelanggaran::MAJOR,
            self::DEVTOOLS => TingkatPelanggaran::MAJOR,
            self::PRINT_ATTEMPT => TingkatPelanggaran::MAJOR,
            self::HEARTBEAT_LOST => TingkatPelanggaran::CRITICAL,
            self::MULTIPLE_LOGIN => TingkatPelanggaran::CRITICAL,
        };
    }
}
