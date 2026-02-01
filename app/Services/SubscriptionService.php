<?php

namespace App\Services;

use App\Mail\Subscription\InvoiceCreated;
use App\Mail\Subscription\SubscriptionActivated;
use App\Mail\Subscription\SubscriptionExpired;
use App\Mail\Subscription\SubscriptionExpiring;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\PaymentProof;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionService
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Create an invoice for a subscription.
     */
    public function createSubscriptionInvoice(
        Company $company,
        SubscriptionPlan $plan,
        string $billingCycle = 'monthly',
        string $paymentMethod = 'bank_transfer'
    ): Invoice {
        $amount = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
        $periodStart = Carbon::now();
        $periodEnd = $billingCycle === 'yearly'
            ? $periodStart->copy()->addYear()
            : $periodStart->copy()->addMonth();

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'amount' => $amount,
            'currency' => config('subscription.currency', 'IDR'),
            'billing_cycle' => $billingCycle,
            'billing_period_start' => $periodStart,
            'billing_period_end' => $periodEnd,
            'status' => Invoice::STATUS_PENDING,
            'due_date' => Carbon::now()->addDays(config('subscription.invoice.due_days', 7)),
            'payment_method' => $paymentMethod,
        ]);

        // Create payment transaction
        PaymentTransaction::create([
            'company_id' => $company->id,
            'invoice_id' => $invoice->id,
            'transaction_id' => PaymentTransaction::generateTransactionId(),
            'amount' => $amount,
            'currency' => config('subscription.currency', 'IDR'),
            'payment_method' => $paymentMethod,
            'status' => PaymentTransaction::STATUS_PENDING,
        ]);

        // Send invoice email to company
        if ($company->email) {
            Mail::to($company->email)->queue(new InvoiceCreated($invoice));
        }

        return $invoice;
    }

    /**
     * Activate a subscription after successful payment.
     */
    public function activateSubscription(
        Company $company,
        SubscriptionPlan $plan,
        string $billingCycle,
        ?Invoice $invoice = null
    ): Company {
        return DB::transaction(function () use ($company, $plan, $billingCycle, $invoice) {
            $now = Carbon::now();
            $endDate = $billingCycle === 'yearly'
                ? $now->copy()->addYear()
                : $now->copy()->addMonth();

            $company->update([
                'subscription_plan_id' => $plan->id,
                'subscription_status' => 'active',
                'subscription_starts_at' => $now,
                'subscription_ends_at' => $endDate,
                'billing_cycle' => $billingCycle,
                'trial_ends_at' => null, // Clear trial
            ]);

            // Update invoice if provided
            if ($invoice) {
                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => $now,
                ]);

                // Update payment transaction
                $invoice->paymentTransactions()->update([
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                ]);
            }

            Log::info("Subscription activated for company {$company->id}", [
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'ends_at' => $endDate,
            ]);

            // Send activation email
            if ($company->email) {
                Mail::to($company->email)->queue(new SubscriptionActivated($company, $plan));
            }

            return $company->fresh();
        });
    }

    /**
     * Upgrade subscription to a new plan.
     */
    public function upgradeSubscription(
        Company $company,
        SubscriptionPlan $newPlan,
        string $billingCycle = null
    ): array {
        $billingCycle = $billingCycle ?? $company->billing_cycle ?? 'monthly';
        $currentPlan = $company->subscriptionPlan;

        // Calculate prorated amount if upgrading mid-cycle
        $proratedAmount = $this->calculateProratedAmount($company, $newPlan, $billingCycle);

        return [
            'current_plan' => $currentPlan,
            'new_plan' => $newPlan,
            'billing_cycle' => $billingCycle,
            'prorated_amount' => $proratedAmount,
            'is_upgrade' => $currentPlan ? $newPlan->price_monthly > $currentPlan->price_monthly : true,
        ];
    }

    /**
     * Downgrade subscription to a new plan.
     */
    public function downgradeSubscription(
        Company $company,
        SubscriptionPlan $newPlan,
        string $billingCycle = null
    ): array {
        $billingCycle = $billingCycle ?? $company->billing_cycle ?? 'monthly';

        return [
            'new_plan' => $newPlan,
            'billing_cycle' => $billingCycle,
            'effective_date' => $company->subscription_ends_at ?? Carbon::now(),
            'message' => 'Downgrade will take effect at the end of your current billing period.',
        ];
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Company $company, string $reason = null): Company
    {
        return DB::transaction(function () use ($company, $reason) {
            $company->update([
                'subscription_status' => 'cancelled',
            ]);

            Log::info("Subscription cancelled for company {$company->id}", [
                'reason' => $reason,
            ]);

            return $company->fresh();
        });
    }

    /**
     * Renew subscription.
     */
    public function renewSubscription(Company $company): Invoice
    {
        $plan = $company->subscriptionPlan;
        $billingCycle = $company->billing_cycle ?? 'monthly';

        return $this->createSubscriptionInvoice($company, $plan, $billingCycle);
    }

    /**
     * Handle expired subscription.
     */
    public function handleExpiredSubscription(Company $company): Company
    {
        return DB::transaction(function () use ($company) {
            $graceEndDate = $company->subscription_ends_at
                ->addDays(config('subscription.grace_period_days', 7));

            if (Carbon::now()->isAfter($graceEndDate)) {
                // Grace period over, suspend account
                $company->update([
                    'subscription_status' => 'suspended',
                ]);
            } else {
                // Still in grace period
                $company->update([
                    'subscription_status' => 'expired',
                ]);
            }

            return $company->fresh();
        });
    }

    /**
     * Calculate prorated amount for mid-cycle plan change.
     */
    public function calculateProratedAmount(
        Company $company,
        SubscriptionPlan $newPlan,
        string $billingCycle
    ): float {
        if (!$company->subscription_ends_at || !$company->subscriptionPlan) {
            // New subscription, no proration
            return $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;
        }

        $now = Carbon::now();
        $endDate = $company->subscription_ends_at;
        $startDate = $company->subscription_starts_at;

        if ($now->isAfter($endDate)) {
            // Subscription already ended, no proration
            return $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;
        }

        // Calculate remaining days
        $totalDays = $startDate->diffInDays($endDate);
        $remainingDays = $now->diffInDays($endDate);
        $usedDays = $totalDays - $remainingDays;

        // Calculate used amount from current plan
        $currentAmount = $company->billing_cycle === 'yearly'
            ? $company->subscriptionPlan->price_yearly
            : $company->subscriptionPlan->price_monthly;
        $dailyRate = $currentAmount / $totalDays;
        $usedAmount = $dailyRate * $usedDays;

        // Calculate new plan daily rate for remaining period
        $newAmount = $billingCycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;
        $newDailyRate = $newAmount / ($billingCycle === 'yearly' ? 365 : 30);
        $proratedNewAmount = $newDailyRate * $remainingDays;

        // Credit from unused current plan + new plan for remaining days
        $creditFromCurrent = $currentAmount - $usedAmount;

        return max(0, $proratedNewAmount - $creditFromCurrent);
    }

    /**
     * Get subscription usage statistics.
     */
    public function getUsageStats(Company $company): array
    {
        $plan = $company->subscriptionPlan;

        if (!$plan) {
            return [
                'has_plan' => false,
                'limits' => [],
                'usage' => [],
            ];
        }

        // Get current month's order count
        $monthlyOrders = $company->salesOrders()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return [
            'has_plan' => true,
            'limits' => [
                'max_users' => $plan->max_users,
                'max_products' => $plan->max_products,
                'max_customers' => $plan->max_customers,
                'max_monthly_orders' => $plan->max_monthly_orders,
            ],
            'usage' => [
                'users' => $company->users()->count(),
                'products' => $company->products()->count() ?? 0,
                'customers' => $company->customers()->count() ?? 0,
                'monthly_orders' => $monthlyOrders,
            ],
            'percentages' => [
                'users' => $plan->max_users > 0
                    ? round(($company->users()->count() / $plan->max_users) * 100, 1)
                    : 0,
                'products' => $plan->max_products > 0
                    ? round((($company->products()->count() ?? 0) / $plan->max_products) * 100, 1)
                    : 0,
                'customers' => $plan->max_customers > 0
                    ? round((($company->customers()->count() ?? 0) / $plan->max_customers) * 100, 1)
                    : 0,
                'monthly_orders' => $plan->max_monthly_orders > 0
                    ? round(($monthlyOrders / $plan->max_monthly_orders) * 100, 1)
                    : 0,
            ],
        ];
    }

    /**
     * Check if company has exceeded any limits.
     */
    public function checkLimits(Company $company): array
    {
        $usage = $this->getUsageStats($company);

        if (!$usage['has_plan']) {
            return ['exceeded' => false, 'warnings' => []];
        }

        $exceeded = [];
        $warnings = [];

        foreach ($usage['percentages'] as $metric => $percentage) {
            if ($percentage >= 100) {
                $exceeded[] = $metric;
            } elseif ($percentage >= 80) {
                $warnings[] = $metric;
            }
        }

        return [
            'exceeded' => !empty($exceeded),
            'exceeded_limits' => $exceeded,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get companies with expiring trials.
     */
    public function getExpiringTrials(int $daysBeforeExpiry = 3): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereDate('trial_ends_at', Carbon::now()->addDays($daysBeforeExpiry)->toDateString())
            ->get();
    }

    /**
     * Get companies with expiring subscriptions.
     */
    public function getExpiringSubscriptions(int $daysBeforeExpiry = 7): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('subscription_status', 'active')
            ->whereNotNull('subscription_ends_at')
            ->whereDate('subscription_ends_at', Carbon::now()->addDays($daysBeforeExpiry)->toDateString())
            ->get();
    }

    /**
     * Send expiration reminder to a company.
     */
    public function sendExpirationReminder(Company $company, int $daysRemaining): void
    {
        if ($company->email) {
            Mail::to($company->email)->queue(new SubscriptionExpiring($company, $daysRemaining));

            Log::info("Sent subscription expiry reminder to company {$company->id}", [
                'days_remaining' => $daysRemaining,
            ]);
        }
    }

    /**
     * Send expiration reminders for all configured reminder days.
     */
    public function sendAllExpirationReminders(): int
    {
        $reminderDays = config('subscription.reminder_days_before_expiry', [7, 3, 1]);
        $sentCount = 0;

        foreach ($reminderDays as $days) {
            $companies = $this->getExpiringSubscriptions($days);

            foreach ($companies as $company) {
                $this->sendExpirationReminder($company, $days);
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Process expired subscriptions and send notifications.
     */
    public function processExpiredSubscriptions(): int
    {
        $expiredCompanies = Company::whereIn('subscription_status', ['active', 'trial'])
            ->where(function ($query) {
                $query->whereDate('subscription_ends_at', '<', Carbon::now())
                    ->orWhereDate('trial_ends_at', '<', Carbon::now());
            })
            ->get();

        $processedCount = 0;

        foreach ($expiredCompanies as $company) {
            $this->handleExpiredSubscription($company);

            // Send expiration email
            if ($company->email) {
                Mail::to($company->email)->queue(new SubscriptionExpired($company));
            }

            $processedCount++;
        }

        if ($processedCount > 0) {
            Log::info("Processed {$processedCount} expired subscriptions");
        }

        return $processedCount;
    }
}
