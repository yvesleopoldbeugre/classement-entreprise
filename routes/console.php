<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalcul quotidien des scores : garde la moyenne globale (C) et les rangs à jour.
Schedule::command('classement:recalculer')
    ->dailyAt('03:00')
    ->withoutOverlapping();
