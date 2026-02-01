<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email
    |--------------------------------------------------------------------------
    |
    | Email address of the platform admin who receives payment verification
    | notifications and other administrative alerts.
    |
    */
    'admin_email' => env('SUBSCRIPTION_ADMIN_EMAIL', null),

    /*
    |--------------------------------------------------------------------------
    | Trial Period
    |--------------------------------------------------------------------------
    |
    | The number of days for the trial period when a new company is created.
    |
    */
    'trial_days' => env('APP_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Grace Period
    |--------------------------------------------------------------------------
    |
    | The number of days after a subscription expires before the account
    | is fully suspended. During this period, users can still access
    | the system in read-only mode.
    |
    */
    'grace_period_days' => env('SUBSCRIPTION_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Expiry Reminder Days
    |--------------------------------------------------------------------------
    |
    | An array of days before expiry to send reminder emails.
    |
    */
    'reminder_days_before_expiry' => [7, 3, 1],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice generation.
    |
    */
    'invoice' => [
        'prefix' => 'INV',
        'due_days' => 7, // Days until invoice is due
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Accounts for Transfer
    |--------------------------------------------------------------------------
    |
    | List of bank accounts available for manual bank transfer payments.
    |
    */
    'bank_accounts' => [
        [
            'bank_name' => env('BANK_1_NAME', 'BCA'),
            'account_name' => env('BANK_1_ACCOUNT_NAME', 'PT HyperBiz Indonesia'),
            'account_number' => env('BANK_1_ACCOUNT_NUMBER', '123-456-7890'),
        ],
        [
            'bank_name' => env('BANK_2_NAME', 'Mandiri'),
            'account_name' => env('BANK_2_ACCOUNT_NAME', 'PT HyperBiz Indonesia'),
            'account_number' => env('BANK_2_ACCOUNT_NUMBER', '098-765-4321'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Proof Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for payment proof file uploads.
    |
    */
    'payment_proof' => [
        'max_size' => env('PAYMENT_PROOF_MAX_SIZE', 5120), // KB (5MB)
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf'],
        'storage_path' => 'payment-proofs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for subscription invoices.
    |
    */
    'currency' => env('SUBSCRIPTION_CURRENCY', 'IDR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Enabled payment methods for subscriptions.
    |
    */
    'payment_methods' => [
        'stripe' => env('ENABLE_STRIPE', true),
        'bank_transfer' => env('ENABLE_BANK_TRANSFER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Renewal
    |--------------------------------------------------------------------------
    |
    | Whether subscriptions should auto-renew by default.
    |
    */
    'auto_renewal' => env('SUBSCRIPTION_AUTO_RENEWAL', true),

    /*
    |--------------------------------------------------------------------------
    | Failed Payment Retry
    |--------------------------------------------------------------------------
    |
    | Configuration for retrying failed payments.
    |
    */
    'failed_payment' => [
        'max_retries' => 3,
        'retry_interval_days' => 2, // Days between retry attempts
    ],

];
