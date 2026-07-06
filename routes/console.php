<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalan tiap hari jam 00:05 — tandai subscription yang sudah lewat ends_at
// jadi 'expired' (akurasi tampilan admin; enforcement akses sudah dijaga
// langsung di query School::activeSubscription(), jadi aman meski command
// ini telat/gagal jalan sehari).
Schedule::command('subscriptions:expire')->dailyAt('00:05');
