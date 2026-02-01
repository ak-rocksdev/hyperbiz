<?php

namespace App\Mail\Subscription;

use App\Models\PaymentProof;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentProof $proof
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Approved - Subscription Activated',
        );
    }

    public function content(): Content
    {
        $transaction = $this->proof->paymentTransaction;
        $invoice = $transaction->invoice;

        return new Content(
            view: 'emails.subscription.payment-approved',
            with: [
                'proof' => $this->proof,
                'invoice' => $invoice,
                'company' => $invoice->company,
                'plan' => $invoice->subscriptionPlan,
            ],
        );
    }
}
