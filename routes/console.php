<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic background scanner for pending WhatsApp tickets
Schedule::command('whatsapp:send-pending-tickets')
    ->everyMinute()
    ->withoutOverlapping();
