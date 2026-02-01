<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ExpireOverdueInvoices extends Command
{
    protected $signature = 'subscription:expire-invoices';

    protected $description = 'Mark overdue pending invoices as expired';

    public function handle(): int
    {
        $this->info('Checking for overdue invoices...');

        $count = Invoice::where('status', Invoice::STATUS_PENDING)
            ->whereDate('due_date', '<', Carbon::now()->subDays(3))
            ->update(['status' => Invoice::STATUS_EXPIRED]);

        $this->info("Expired {$count} overdue invoice(s).");

        return self::SUCCESS;
    }
}
