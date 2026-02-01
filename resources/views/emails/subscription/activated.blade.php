<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Activated</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
        .plan-box { background: #ecfdf5; border-radius: 8px; padding: 20px; margin: 20px 0; text-align: center; }
        .plan-name { font-size: 24px; font-weight: bold; color: #10b981; }
        .features { margin: 20px 0; }
        .feature { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .feature:last-child { border-bottom: none; }
        .feature::before { content: "✓ "; color: #10b981; font-weight: bold; }
        .btn { display: inline-block; background: #10b981; color: white; text-decoration: none; padding: 12px 30px; border-radius: 6px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Subscription Activated!</h1>
        </div>
        <div class="content">
            <div class="success-icon">🎉</div>

            <p>Dear {{ $company->name }},</p>
            <p>Great news! Your subscription has been successfully activated. You now have full access to all features included in your plan.</p>

            <div class="plan-box">
                <div class="plan-name">{{ $plan->name }} Plan</div>
                <p>Your subscription is now active</p>
            </div>

            <h3>Your Plan Includes:</h3>
            <div class="features">
                <div class="feature">Up to {{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }} Users</div>
                <div class="feature">Up to {{ $plan->max_products == -1 ? 'Unlimited' : $plan->max_products }} Products</div>
                <div class="feature">Up to {{ $plan->max_customers == -1 ? 'Unlimited' : $plan->max_customers }} Customers</div>
                <div class="feature">{{ $plan->max_monthly_orders == -1 ? 'Unlimited' : $plan->max_monthly_orders }} Monthly Orders</div>
                @foreach($plan->features ?? [] as $feature)
                <div class="feature">{{ $feature }}</div>
                @endforeach
            </div>

            <p style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="btn">Go to Dashboard</a>
            </p>

            <p>Thank you for choosing {{ config('app.name') }}. If you need any assistance, our support team is here to help.</p>

            <p>Best regards,<br>The {{ config('app.name') }} Team</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
