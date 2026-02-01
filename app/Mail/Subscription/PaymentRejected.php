<?php

namespace App\Mail\Subscription;

use App\Models\PaymentProof;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentProof $proof
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Verification Failed - Action Required',
        );
    }

    public function content(): Content
    {
        $transaction = $this->proof->paymentTransaction;
        $invoice = $transaction->invoice;

        return new Content(
            view: 'emails.subscription.payment-rejected',
            with: [
                'proof' => $this->proof,
                'invoice' => $invoice,
                'company' => $invoice->company,
                'rejectionReason' => $this->proof->rejection_reason,
                'retryUrl' => route('subscription.payment-proof', $invoice->id),
            ],
        );
    }
}
