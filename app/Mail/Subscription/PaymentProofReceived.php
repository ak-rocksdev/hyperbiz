<?php

namespace App\Mail\Subscription;

use App\Models\PaymentProof;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentProofReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PaymentProof $proof
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Proof Received - Awaiting Verification',
        );
    }

    public function content(): Content
    {
        $transaction = $this->proof->paymentTransaction;
        $invoice = $transaction->invoice;

        return new Content(
            view: 'emails.subscription.payment-proof-received',
            with: [
                'proof' => $this->proof,
                'invoice' => $invoice,
                'company' => $invoice->company,
            ],
        );
    }
}
