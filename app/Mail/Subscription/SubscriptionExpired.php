<?php

namespace App\Mail\Subscription;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpired extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Company $company
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Subscription Has Expired - Action Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription.expired',
            with: [
                'company' => $this->company,
                'renewUrl' => route('subscription.plans'),
            ],
        );
    }
}
