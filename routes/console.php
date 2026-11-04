<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Cron Job Otomatis Deteksi Bahan Kedaluwarsa & Picu Resep AI Setiap Pagi Jam 07:00 WIB
Schedule::command('resepify:check-expirations')->dailyAt('07:00')->withoutOverlapping();
