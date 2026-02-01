@extends('pdf.layouts.document')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@php
    $showWatermark = false;
    $watermarkText = '';
    if($invoice->status === 'pending') {
        $showWatermark = true;
        $watermarkText = 'UNPAID';
    } elseif($invoice->status === 'cancelled') {
        $showWatermark = true;
        $watermarkText = 'CANCELLED';
    } elseif($invoice->status === 'expired') {
        $showWatermark = true;
        $watermarkText = 'EXPIRED';
    }

    // Build logo as base64 for DomPDF compatibility
    $logoBase64 = null;
    if ($company->logo) {
        $logoPath = storage_path('app/public/' . $company->logo);
        if (file_exists($logoPath)) {
            $logoContent = file_get_contents($logoPath);
            $logoMime = mime_content_type($logoPath);
            $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoContent);
        }
    }

    $statusColors = [
        'paid' => '#10b981',
        'pending' => '#f59e0b',
        'awaiting_verification' => '#3b82f6',
        'cancelled' => '#6b7280',
        'expired' => '#6b7280',
        'failed' => '#ef4444',
    ];
    $statusColor = $statusColors[$invoice->status] ?? '#6b7280';
@endphp

@section('content')
    {{-- Header Accent Bar --}}
    <div class="header-accent"></div>

    {{-- Header with Logo and Company Info --}}
    <div class="header-main">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="{{ $company->name }}" class="company-logo">
                    @else
                        <div style="font-size: 18pt; font-weight: 700; color: #1e3a5f;">HyperBiz</div>
                    @endif
                </td>
                <td class="company-cell">
                    <div class="company-name">HyperBiz</div>
                    <div class="company-details">
                        <div class="company-details-row">Enterprise Resource Planning System</div>
                        <div class="company-details-row">support@hyperbiz.com</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Document Title Section --}}
    <div class="title-section">
        <table class="title-table">
            <tr>
                <td style="vertical-align: middle;">
                    <div class="document-type">INVOICE</div>
                </td>
                <td class="document-number-wrapper">
                    <div class="document-number-box">
                        <div class="document-number-label">Invoice No.</div>
                        <div class="document-number-value">{{ $invoice->invoice_number }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Bill To & Invoice Details --}}
    <div class="info-section">
        <table class="info-table">
            <tr>
                {{-- Bill To Card --}}
                <td class="info-card info-card-left">
                    <div class="info-header">Bill To</div>
                    <div class="info-title">{{ $company->name ?? 'N/A' }}</div>
                    <div class="info-text">
                        @if($company->address)
                            <div class="info-row">{{ $company->address }}</div>
                        @endif
                        @if($company->phone)
                            <div class="info-row">Phone: {{ $company->phone }}</div>
                        @endif
                        @if($company->email)
                            <div class="info-row">Email: {{ $company->email }}</div>
                        @endif
                    </div>
                </td>

                <td class="info-spacer"></td>

                {{-- Invoice Details Card --}}
                <td class="info-card info-card-right">
                    <div class="info-header">Invoice Details</div>
                    <table class="detail-table">
                        <tr class="detail-row">
                            <td class="detail-label">Issue Date:</td>
                            <td class="detail-value">{{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</td>
                        </tr>
                        <tr class="detail-row">
                            <td class="detail-label">Due Date:</td>
                            <td class="detail-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                        </tr>
                        @if($invoice->paid_at)
                        <tr class="detail-row">
                            <td class="detail-label">Paid Date:</td>
                            <td class="detail-value">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') }}</td>
                        </tr>
                        @endif
                        <tr class="detail-row">
                            <td class="detail-label">Status:</td>
                            <td class="detail-value">
                                <span style="color: {{ $statusColor }}; font-weight: 600; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $invoice->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    {{-- Items Table --}}
    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr class="items-header">
                    <th style="width: 50%;">Description</th>
                    <th style="width: 15%; text-align: center;">Billing Cycle</th>
                    <th style="width: 15%; text-align: center;">Period</th>
                    <th style="width: 20%; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="item-row">
                    <td>
                        <div style="font-weight: 600; color: #1e3a5f;">{{ $invoice->subscriptionPlan->name ?? 'Subscription Plan' }}</div>
                        <div style="font-size: 8pt; color: #6b7280; margin-top: 3px;">
                            {{ $invoice->subscriptionPlan->description ?? 'Subscription service' }}
                        </div>
                    </td>
                    <td style="text-align: center;">
                        {{ ucfirst($invoice->billing_cycle ?? 'monthly') }}
                    </td>
                    <td style="text-align: center;">
                        @if($invoice->billing_period_start && $invoice->billing_period_end)
                            {{ \Carbon\Carbon::parse($invoice->billing_period_start)->format('d M') }} -
                            {{ \Carbon\Carbon::parse($invoice->billing_period_end)->format('d M Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Totals Section --}}
    <div class="totals-section">
        <table class="totals-table">
            <tr class="total-row">
                <td class="total-label">Subtotal</td>
                <td class="total-value">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td class="total-label">Tax (0%)</td>
                <td class="total-value">Rp 0</td>
            </tr>
            <tr class="grand-total-row">
                <td class="grand-total-label">Total Due</td>
                <td class="grand-total-value">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    {{-- Payment Information --}}
    @if($invoice->status !== 'paid')
    <div style="margin-top: 20px; padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
        <div style="font-weight: 600; color: #1e3a5f; margin-bottom: 10px; font-size: 10pt;">Payment Information</div>
        <div style="font-size: 9pt; color: #475569;">
            <p style="margin: 5px 0;">Please transfer the exact amount to one of the following bank accounts:</p>
            @foreach(config('subscription.bank_accounts', []) as $bank)
            <div style="margin: 10px 0; padding: 10px; background-color: #fff; border: 1px solid #e2e8f0; border-radius: 4px;">
                <div style="font-weight: 600;">{{ $bank['bank_name'] }}</div>
                <div>Account Name: {{ $bank['account_name'] }}</div>
                <div>Account Number: {{ $bank['account_number'] }}</div>
            </div>
            @endforeach
            <p style="margin-top: 10px; font-style: italic;">Include invoice number <strong>{{ $invoice->invoice_number }}</strong> in the transfer description.</p>
        </div>
    </div>
    @endif

    {{-- Footer Notes --}}
    <div class="notes-section">
        <div class="notes-header">Notes</div>
        <div class="notes-content">
            <p>Thank you for your subscription to HyperBiz.</p>
            <p>For any questions regarding this invoice, please contact support@hyperbiz.com</p>
        </div>
    </div>

    {{-- Watermark --}}
    @if($showWatermark)
    <div class="watermark">{{ $watermarkText }}</div>
    @endif
@endsection
