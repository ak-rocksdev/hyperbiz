<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Payment Proof Pending</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .alert-box { background: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0; }
        .details-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 600; }
        .btn { display: inline-block; background: #dc2626; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Action Required: Payment Verification</h1>
        </div>
        <div class="content">
            <div class="alert-box">
                <strong>New payment proof requires your attention!</strong>
                <p style="margin: 5px 0 0 0;">A customer has uploaded a payment proof that needs to be verified.</p>
            </div>

            <div class="details-box">
                <h3 style="margin-top: 0;">Payment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Company</span>
                    <span class="detail-value">{{ $company->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Invoice Number</span>
                    <span class="detail-value">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Invoice Amount</span>
                    <span class="detail-value">{{ $invoice->formatted_amount }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Bank Name</span>
                    <span class="detail-value">{{ $proof->bank_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Account Name</span>
                    <span class="detail-value">{{ $proof->account_name }}</span>
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
                <a href="{{ $verificationUrl }}" class="btn">Review Payment Proof</a>
            </p>

            <p>Please verify this payment at your earliest convenience.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
