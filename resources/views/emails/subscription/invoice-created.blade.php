<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .invoice-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .invoice-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .invoice-row:last-child { border-bottom: none; }
        .invoice-label { color: #6b7280; }
        .invoice-value { font-weight: 600; }
        .amount { font-size: 24px; color: #3b82f6; font-weight: bold; }
        .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
        .bank-info { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Created</h1>
        </div>
        <div class="content">
            <p>Dear {{ $company->name }},</p>
            <p>A new invoice has been created for your subscription. Please find the details below:</p>

            <div class="invoice-box">
                <div class="invoice-row">
                    <span class="invoice-label">Invoice Number</span>
                    <span class="invoice-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Plan</span>
                    <span class="invoice-value">{{ $plan->name }} ({{ ucfirst($invoice->billing_cycle) }})</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Due Date</span>
                    <span class="invoice-value">{{ $invoice->due_date->format('d M Y') }}</span>
                </div>
                <div class="invoice-row">
                    <span class="invoice-label">Amount</span>
                    <span class="amount">{{ $invoice->formatted_amount }}</span>
                </div>
            </div>

            @if($invoice->payment_method === 'bank_transfer')
            <div class="bank-info">
                <strong>Bank Transfer Payment</strong>
                <p>Please transfer the amount to one of our bank accounts and upload your payment proof through the dashboard.</p>
            </div>
            @endif

            <p style="text-align: center;">
                <a href="{{ route('subscription.index') }}" class="btn">View Invoice</a>
            </p>

            <p>If you have any questions, please don't hesitate to contact our support team.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
