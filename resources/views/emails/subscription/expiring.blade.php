<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expiring Soon</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #f59e0b; color: white; padding: 20px; text-align: center; }
        .header.urgent { background: #dc2626; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .countdown { text-align: center; margin: 30px 0; }
        .countdown-number { font-size: 72px; font-weight: bold; color: {{ $daysRemaining <= 1 ? '#dc2626' : '#f59e0b' }}; }
        .countdown-text { font-size: 18px; color: #6b7280; }
        .warning-box { background: {{ $daysRemaining <= 1 ? '#fef2f2' : '#fef3c7' }}; border-left: 4px solid {{ $daysRemaining <= 1 ? '#dc2626' : '#f59e0b' }}; padding: 15px; margin: 20px 0; }
        .plan-box { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $daysRemaining <= 1 ? 'urgent' : '' }}">
            <h1>Subscription Expiring Soon</h1>
        </div>
        <div class="content">
            <div class="countdown">
                <div class="countdown-number">{{ $daysRemaining }}</div>
                <div class="countdown-text">Day{{ $daysRemaining !== 1 ? 's' : '' }} Remaining</div>
            </div>

            <p>Dear {{ $company->name }},</p>
            <p>Your subscription is expiring soon. To continue enjoying all features without interruption, please renew your subscription.</p>

            <div class="warning-box">
                <strong>Expiration Date: {{ $expiresAt->format('d M Y') }}</strong>
                <p style="margin: 5px 0 0 0;">After this date, you will lose access to premium features.</p>
            </div>

            <div class="plan-box">
                <h3 style="margin-top: 0;">Current Plan: {{ $plan->name }}</h3>
                <p>Renew now to keep your:</p>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>{{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }} Users</li>
                    <li>{{ $plan->max_products == -1 ? 'Unlimited' : $plan->max_products }} Products</li>
                    <li>{{ $plan->max_customers == -1 ? 'Unlimited' : $plan->max_customers }} Customers</li>
                    <li>All your data and settings</li>
                </ul>
            </div>

            <p style="text-align: center;">
                <a href="{{ $renewUrl }}" class="btn">Renew Subscription Now</a>
            </p>

            <p>If you have any questions about renewing your subscription, please contact our support team.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
