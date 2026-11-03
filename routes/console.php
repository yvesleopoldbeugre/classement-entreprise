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

// Purge mensuelle des visites de plus de 12 mois (les actions sont conservées).
Schedule::command('stats:purger')
    ->monthlyOn(1, '04:00')
    ->withoutOverlapping();

// Purge quotidienne du chat : présences périmées + conversations inactives.
Schedule::command('chat:purger')
    ->dailyAt('04:30')
    ->withoutOverlapping();

// Enrichissement hebdomadaire des nouvelles fiches depuis leur site web (par lots).
Schedule::command('entreprises:enrichir --limite=100')
    ->weeklyOn(0, '05:00')
    ->withoutOverlapping();
