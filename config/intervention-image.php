<?php

use Intervention\Image\Drivers\Gd\Driver;

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Driver GD dipakai karena ekstensi GD tersedia di server aplikasi.
    |
    */

    'driver' => env('IMAGE_DRIVER', Driver::class),

    /*
    |--------------------------------------------------------------------------
    | Configuration Options
    |--------------------------------------------------------------------------
    */

    'options' => [
        'autoOrientation' => false,
        'decodeAnimation' => true,
        'backgroundColor' => 'ffffff',
        'strip' => true,
    ],
];
