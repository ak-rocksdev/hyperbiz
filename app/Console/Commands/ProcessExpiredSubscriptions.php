<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessExpiredSubscriptions extends Command
{
    protected $signature = 'subscription:process-expired';

    protected $description = 'Process expired trials and subscriptions, update their status';

    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Processing expired subscriptions...');

        $count = $subscriptionService->processExpiredSubscriptions();

        $this->info("Processed {$count} expired subscription(s).");

        return self::SUCCESS;
    }
}
