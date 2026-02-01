<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscription:send-reminders';

    protected $description = 'Send expiration reminder emails for subscriptions and trials about to expire';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Sending subscription expiration reminders...');

        $sentCount = $subscriptionService->sendAllExpirationReminders();

        $this->info("Sent {$sentCount} reminder email(s).");

        return self::SUCCESS;
    }
}
