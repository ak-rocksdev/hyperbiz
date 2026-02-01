<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Subscription scheduled tasks
Schedule::command('subscription:process-expired')
    ->daily()
    ->at('01:00')
    ->withoutOverlapping()
    ->description('Process expired trials and subscriptions');

Schedule::command('subscription:send-reminders')
    ->daily()
    ->at('08:00')
    ->withoutOverlapping()
    ->description('Send subscription expiration reminders');

Schedule::command('subscription:expire-invoices')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->description('Expire overdue unpaid invoices');
