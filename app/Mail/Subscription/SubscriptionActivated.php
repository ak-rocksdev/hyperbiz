<?php

namespace App\Mail\Subscription;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionActivated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public SubscriptionPlan $plan
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Subscription Activated - ' . $this->plan->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.activated',
            with: [
                'company' => $this->company,
                'plan' => $this->plan,
            ],
        );
    }
}
