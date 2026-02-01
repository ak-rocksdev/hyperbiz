<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Proof Received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .status-box { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; }
        .details-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 600; }
        .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Proof Received</h1>
        </div>
        <div class="content">
            <p>Dear {{ $company->name }},</p>
            <p>We have received your payment proof. Our team will verify your payment within 1-2 business days.</p>

            <div class="status-box">
                <strong>Status: Awaiting Verification</strong>
                <p style="margin: 5px 0 0 0;">We will notify you once your payment has been verified.</p>
            </div>

            <div class="details-box">
                <h3 style="margin-top: 0;">Payment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number</span>
                    <span class="detail-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bank Name</span>
                    <span class="detail-value">{{ $proof->bank_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transfer Date</span>
                    <span class="detail-value">{{ $proof->transfer_date->format('d M Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transfer Amount</span>
                    <span class="detail-value">{{ $proof->formatted_transfer_amount }}</span>
                </div>
            </div>

            <p style="text-align: center;">
                <a href="{{ route('subscription.index') }}" class="btn">View Status</a>
            </p>

            <p>Thank you for your patience. If you have any questions, please contact our support team.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
