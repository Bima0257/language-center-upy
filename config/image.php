<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas Ukuran Gambar
    |--------------------------------------------------------------------------
    |
    | Semua gambar yang diunggah divalidasi maksimal sebesar nilai ini (KB)
    | dan hasil kompresi juga dipastikan tidak melebihi batas tersebut.
    |
    */

    'max_kb' => (int) env('IMAGE_MAX_KB', 2048),

    /*
    |--------------------------------------------------------------------------
    | Dimensi dan Kualitas Kompresi
    |--------------------------------------------------------------------------
    */

    'max_dimension' => (int) env('IMAGE_MAX_DIMENSION', 1920),

    'min_dimension' => (int) env('IMAGE_MIN_DIMENSION', 1280),

    'webp_quality' => (int) env('IMAGE_WEBP_QUALITY', 80),

    'min_quality' => (int) env('IMAGE_MIN_QUALITY', 50),

    'absolute_max_dimension' => (int) env('IMAGE_ABSOLUTE_MAX_DIMENSION', 12000),

];
