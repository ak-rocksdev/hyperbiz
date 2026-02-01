<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .success-box { background: #ecfdf5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; }
        .details-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 600; }
        .btn { display: inline-block; background: #10b981; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Approved!</h1>
        </div>
        <div class="content">
            <div class="success-icon">✅</div>

            <p>Dear {{ $company->name }},</p>
            <p>Great news! Your payment has been verified and approved. Your subscription is now active.</p>

            <div class="success-box">
                <strong>Your subscription has been activated!</strong>
                <p style="margin: 5px 0 0 0;">You now have full access to all features in the {{ $plan->name }} plan.</p>
            </div>

            <div class="details-box">
                <h3 style="margin-top: 0;">Payment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number</span>
                    <span class="detail-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Plan</span>
                    <span class="detail-value">{{ $plan->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Billing Cycle</span>
                    <span class="detail-value">{{ ucfirst($invoice->billing_cycle) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value">{{ $invoice->formatted_amount }}</span>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="btn">Go to Dashboard</a>
            </p>

            <p>Thank you for your payment. If you have any questions, please don't hesitate to contact us.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
