<?php

use App\Modules\DataInduk\Providers\DataIndukModuleServiceProvider;
use App\Modules\Keamanan\Providers\KeamananModuleServiceProvider;
use App\Modules\Laporan\Providers\LaporanModuleServiceProvider;
use App\Modules\PelaksanaanUjian\Providers\PelaksanaanUjianModuleServiceProvider;
use App\Modules\Pengawasan\Providers\PengawasanModuleServiceProvider;
use App\Modules\Pengguna\Providers\PenggunaModuleServiceProvider;
use App\Modules\Penilaian\Providers\PenilaianModuleServiceProvider;
use App\Modules\Penjadwalan\Providers\PenjadwalanModuleServiceProvider;
use App\Modules\Ujian\Providers\UjianModuleServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    UjianModuleServiceProvider::class,
    PenjadwalanModuleServiceProvider::class,
    PelaksanaanUjianModuleServiceProvider::class,
    KeamananModuleServiceProvider::class,
    PengawasanModuleServiceProvider::class,
    PenilaianModuleServiceProvider::class,
    LaporanModuleServiceProvider::class,
    DataIndukModuleServiceProvider::class,
    PenggunaModuleServiceProvider::class,
];
