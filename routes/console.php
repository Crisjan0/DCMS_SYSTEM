<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Regenerate clinic forecasts every Monday, 6:00 AM — before the week starts.
Schedule::command('forecast:generate')->weeklyOn(1, '06:00');
