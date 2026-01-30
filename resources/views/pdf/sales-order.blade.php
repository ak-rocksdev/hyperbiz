@extends('pdf.layouts.document')

@section('title', 'Sales Order - ' . $salesOrder->so_number)

@php
    $showWatermark = false;
    $watermarkText = '';
    if($salesOrder->status === 'draft') {
        $showWatermark = true;
        $watermarkText = 'DRAFT';
    } elseif($salesOrder->status === 'cancelled') {
        $showWatermark = true;
        $watermarkText = 'CANCELLED';
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
                        <div style="font-size: 18pt; font-weight: 700; color: #1e3a5f;">{{ $company->name ?? 'HyperBiz' }}</div>
                    @endif
                </td>
                <td class="company-cell">
                    <div class="company-name">{{ $company->name ?? 'HyperBiz' }}</div>
                    <div class="company-details">
                        @if($company->address)<div class="company-details-row">{{ $company->address }}</div>@endif
                        @if($company->phone)<div class="company-details-row">Phone: {{ $company->phone }}</div>@endif
                        @if($company->email)<div class="company-details-row">Email: {{ $company->email }}</div>@endif
                        @if($company->website)<div class="company-details-row">{{ $company->website }}</div>@endif
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
                    <div class="document-type">Sales Order</div>
                </td>
                <td class="document-number-wrapper">
                    <div class="document-number-box">
                        <div class="document-number-label">Document No.</div>
                        <div class="document-number-value">{{ $salesOrder->so_number }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Customer & Order Info Cards --}}
        <div class="info-section">
            <table class="info-table">
                <tr>
                    {{-- Customer Card --}}
                    <td class="info-card info-card-left">
                        <div class="info-header">Bill To</div>
                        <div class="info-title">{{ $salesOrder->customer->client_name ?? 'N/A' }}</div>
                        <div class="info-text">
                            @if($salesOrder->customer?->address)
                                <div class="info-row">{{ $salesOrder->customer->address->full_address ?? '' }}</div>
                            @endif
                            @if($salesOrder->customer?->client_phone_number)
                                <div class="info-row">Phone: {{ $salesOrder->customer->client_phone_number }}</div>
                            @endif
                            @if($salesOrder->customer?->email)
                                <div class="info-row">Email: {{ $salesOrder->customer->email }}</div>
                            @endif
                        </div>
                    </td>

                    <td class="info-spacer"></td>

                    {{-- Order Details Card --}}
                    <td class="info-card info-card-right">
                        <div class="info-header">Order Details</div>
                        <table class="detail-table">
                            <tr class="detail-row">
                                <td class="detail-label">Order Date</td>
                                <td class="detail-value">{{ $salesOrder->order_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Due Date</td>
                                <td class="detail-value">{{ $salesOrder->due_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Status</td>
                                <td class="detail-value">
                                    <span class="badge badge-{{ $salesOrder->status }}">{{ strtoupper($salesOrder->status) }}</span>
                                </td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Payment</td>
                                <td class="detail-value">
                                    <span class="badge badge-{{ $salesOrder->payment_status }}">{{ strtoupper($salesOrder->payment_status) }}</span>
                                </td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Currency</td>
                                <td class="detail-value">{{ $salesOrder->currency_code ?? 'IDR' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Shipping Address (if different) --}}
        @if($salesOrder->shipping_address)
        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td class="info-card" style="width: 100%;">
                        <div class="info-header">Ship To</div>
                        <div class="info-text">{{ $salesOrder->shipping_address }}</div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        {{-- Items Table --}}
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 36%;">Product</th>
                        <th style="width: 10%;" class="text-center">Qty</th>
                        <th style="width: 10%;" class="text-center">UOM</th>
                        <th style="width: 15%;" class="text-right">Unit Price</th>
                        <th style="width: 10%;" class="text-center">Disc</th>
                        <th style="width: 15%;" class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrder->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product->name ?? 'N/A' }}</div>
                            @if($item->product?->sku)
                                <div class="item-sku">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2, ',', '.') }}</td>
                        <td class="text-center">{{ $item->uom->name ?? '-' }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item->discount_percentage > 0 ? number_format($item->discount_percentage, 0) . '%' : '-' }}</td>
                        <td class="text-right font-semibold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Totals Section --}}
        <div class="totals-section">
            <table class="totals-table-outer">
                <tr>
                    {{-- Notes --}}
                    <td class="notes-cell">
                        @if($salesOrder->notes)
                        <div class="notes-box">
                            <div class="notes-title">Notes</div>
                            <div class="notes-content">{{ $salesOrder->notes }}</div>
                        </div>
                        @endif
                    </td>

                    {{-- Totals --}}
                    <td class="totals-cell">
                        <table class="totals-table">
                            <tr>
                                <td class="label-cell">Subtotal</td>
                                <td class="value-cell">{{ number_format($salesOrder->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if($salesOrder->discount_amount > 0)
                            <tr>
                                <td class="label-cell">
                                    Discount
                                    @if($salesOrder->discount_type === 'percentage')
                                        ({{ number_format($salesOrder->discount_value, 0) }}%)
                                    @endif
                                </td>
                                <td class="value-cell discount-value">-{{ number_format($salesOrder->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($salesOrder->tax_enabled && $salesOrder->tax_amount > 0)
                            <tr>
                                <td class="label-cell">{{ $salesOrder->tax_name ?? 'Tax' }} ({{ number_format($salesOrder->tax_percentage, 0) }}%)</td>
                                <td class="value-cell">{{ number_format($salesOrder->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($salesOrder->shipping_fee > 0)
                            <tr>
                                <td class="label-cell">Shipping Fee</td>
                                <td class="value-cell">{{ number_format($salesOrder->shipping_fee, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="grand-total-row">
                                <td class="label-cell">Grand Total</td>
                                <td class="value-cell">{{ $salesOrder->currency_code ?? 'IDR' }} {{ number_format($salesOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            @if($salesOrder->amount_paid > 0)
                            <tr>
                                <td class="label-cell">Amount Paid</td>
                                <td class="value-cell paid-value">{{ number_format($salesOrder->amount_paid, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell font-semibold">Balance Due</td>
                                <td class="value-cell due-value">{{ $salesOrder->currency_code ?? 'IDR' }} {{ number_format($salesOrder->grand_total - $salesOrder->amount_paid, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Payment Terms & Bank Details --}}
        @if(isset($bankDetails) && $bankDetails)
        <div class="terms-section">
            <div class="notes-box">
                <div class="notes-title">Payment Information</div>
                <div class="notes-content">
                    <strong>Bank:</strong> {{ $bankDetails['bank_name'] ?? '-' }}<br>
                    <strong>Account No:</strong> {{ $bankDetails['account_number'] ?? '-' }}<br>
                    <strong>Account Name:</strong> {{ $bankDetails['account_name'] ?? '-' }}
                </div>
            </div>
        </div>
        @endif

        {{-- Signature Section --}}
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td class="signature-cell">
                        <div class="signature-line">
                            <div class="signature-title">Prepared By</div>
                            <div class="signature-subtitle">{{ $preparedBy ?? 'Authorized Personnel' }}</div>
                        </div>
                    </td>
                    <td style="width: 5%;"></td>
                    <td class="signature-cell">
                        <div class="signature-line">
                            <div class="signature-title">Approved By</div>
                            <div class="signature-subtitle">Management</div>
                        </div>
                    </td>
                    <td style="width: 5%;"></td>
                    <td class="signature-cell">
                        <div class="signature-line">
                            <div class="signature-title">Received By</div>
                            <div class="signature-subtitle">Customer</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
@endsection
