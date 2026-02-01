<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification Failed</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .warning-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .error-box { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
        .reason-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Verification Failed</h1>
        </div>
        <div class="content">
            <div class="warning-icon">⚠️</div>

            <p>Dear {{ $company->name }},</p>
            <p>Unfortunately, we were unable to verify your payment proof. Please see the details below:</p>

            <div class="error-box">
                <strong>Invoice: {{ $invoice->invoice_number }}</strong>
                <p style="margin: 5px 0 0 0;">Your payment proof has been rejected.</p>
            </div>

            <div class="reason-box">
                <h3 style="margin-top: 0; color: #dc2626;">Rejection Reason</h3>
                <p>{{ $rejectionReason }}</p>
            </div>

            <p>Please review the rejection reason and upload a new payment proof. Common issues include:</p>
            <ul>
                <li>Blurry or unreadable proof images</li>
                <li>Incorrect transfer amount</li>
                <li>Transfer to wrong bank account</li>
                <li>Missing or incomplete information</li>
            </ul>

            <p style="text-align: center;">
                <a href="{{ $retryUrl }}" class="btn">Upload New Payment Proof</a>
            </p>

            <p>If you believe this was an error or need assistance, please contact our support team.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
