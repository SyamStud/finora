<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Fund price source
    |--------------------------------------------------------------------------
    |
    | Finora saat ini hanya memantau satu produk: Sucorinvest Money Market
    | Fund (RDPU, kode Bibit RD1657). NAV diambil dari halaman publik Bibit,
    | yang menyimpan data di __NEXT_DATA__ — tidak perlu login/API key.
    |
    | NAV dipublikasikan harian, sehingga hasil fetch di-cache untuk
    | menghindari request berulang ke pihak ketiga.
    |
    */

    'rdpu' => [
        'name' => (string) env('FUND_NAME', 'Sucorinvest Money Market Fund'),
        'code' => (string) env('FUND_CODE', 'RD1657'),
        'url' => (string) env(
            'FUND_NAV_URL',
            'https://bibit.id/reksadana/RD1657/sucorinvest-money-market-fund'
        ),
        'cache_seconds' => (int) env('FUND_NAV_CACHE_SECONDS', 3600),
        'timeout_seconds' => (int) env('FUND_NAV_TIMEOUT_SECONDS', 15),
    ],

];
