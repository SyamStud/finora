<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NAV reksa dana dipublikasikan setelah pasar tutup; ambil sekali sehari.
Schedule::command('fund:refresh-prices')->dailyAt('20:00')->withoutOverlapping();
