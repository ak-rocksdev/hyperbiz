<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #6b7280; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .expired-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .expired-box { background: #f3f4f6; border-left: 4px solid #6b7280; padding: 15px; margin: 20px 0; }
        .loss-box { background: #fef2f2; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .loss-item { padding: 8px 0; border-bottom: 1px solid #fecaca; }
        .loss-item:last-child { border-bottom: none; }
        .loss-item::before { content: "✕ "; color: #dc2626; font-weight: bold; }
        .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subscription Expired</h1>
        </div>
        <div class="content">
            <div class="expired-icon">⏰</div>

            <p>Dear {{ $company->name }},</p>
            <p>Your subscription has expired. Your account is now limited to basic features only.</p>

            <div class="expired-box">
                <strong>Your subscription has ended</strong>
                <p style="margin: 5px 0 0 0;">Renew now to restore full access to all premium features.</p>
            </div>

            <div class="loss-box">
                <h3 style="margin-top: 0; color: #dc2626;">Features Now Unavailable:</h3>
                <div class="loss-item">Creating new transactions</div>
                <div class="loss-item">Adding new products beyond limit</div>
                <div class="loss-item">Adding new customers beyond limit</div>
                <div class="loss-item">Advanced reports and analytics</div>
                <div class="loss-item">Priority support</div>
            </div>

            <p><strong>Don't worry!</strong> Your data is safe and will be fully restored once you renew your subscription.</p>

            <p style="text-align: center;">
                <a href="{{ $renewUrl }}" class="btn">Renew Your Subscription</a>
            </p>

            <p>If you have any questions or need assistance, please contact our support team.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
