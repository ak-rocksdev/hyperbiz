<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | Your Stripe publishable and secret keys. These can be found in your
    | Stripe Dashboard under Developers > API Keys.
    |
    */
    'key' => env('STRIPE_KEY', ''),
    'secret' => env('STRIPE_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe Webhook Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret for verifying incoming webhook events.
    | This can be found in your Stripe Dashboard under Developers > Webhooks.
    |
    */
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Stripe Mode
    |--------------------------------------------------------------------------
    |
    | Whether to use Stripe in test mode or live mode.
    |
    */
    'test_mode' => env('STRIPE_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for Stripe payments.
    | Note: Stripe requires lowercase currency codes.
    |
    */
    'currency' => strtolower(env('STRIPE_CURRENCY', 'idr')),

    /*
    |--------------------------------------------------------------------------
    | Checkout Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe Checkout sessions.
    |
    */
    'checkout' => [
        // URL to redirect after successful payment
        'success_url' => env('STRIPE_SUCCESS_URL', '/subscription/success'),

        // URL to redirect after cancelled payment
        'cancel_url' => env('STRIPE_CANCEL_URL', '/subscription/cancelled'),

        // Payment method types to allow
        'payment_method_types' => ['card'],

        // Billing address collection
        'billing_address_collection' => 'auto',

        // Allow promotion codes
        'allow_promotion_codes' => false,

        // Session expiration (in minutes)
        'expires_after_minutes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Events
    |--------------------------------------------------------------------------
    |
    | List of Stripe webhook events to handle.
    |
    */
    'webhook_events' => [
        'checkout.session.completed',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'invoice.paid',
        'invoice.payment_failed',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Company Information for Stripe
    |--------------------------------------------------------------------------
    |
    | Information displayed to customers during checkout.
    |
    */
    'company' => [
        'name' => env('APP_NAME', 'HyperBiz'),
        'statement_descriptor' => env('STRIPE_STATEMENT_DESCRIPTOR', 'HYPERBIZ'),
    ],

];
