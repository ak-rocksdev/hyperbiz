<?php

namespace App\Mail\Subscription;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiring extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public int $daysRemaining
    ) {}

    public function envelope(): Envelope
    {
        $urgency = $this->daysRemaining <= 1 ? 'URGENT: ' : '';
        return new Envelope(
            subject: $urgency . 'Your Subscription Expires in ' . $this->daysRemaining . ' Day(s)',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.expiring',
            with: [
                'company' => $this->company,
                'plan' => $this->company->subscriptionPlan,
                'daysRemaining' => $this->daysRemaining,
                'expiresAt' => $this->company->subscription_ends_at,
                'renewUrl' => route('subscription.plans'),
            ],
        );
    }
}
