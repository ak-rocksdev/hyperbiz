@extends('pdf.layouts.document')

@section('title', 'Purchase Order - ' . $purchaseOrder->po_number)

@php
    $showWatermark = false;
    $watermarkText = '';
    if($purchaseOrder->status === 'draft') {
        $showWatermark = true;
        $watermarkText = 'DRAFT';
    } elseif($purchaseOrder->status === 'cancelled') {
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
                        <div style="font-size: 16pt; font-weight: 700; color: #1e3a5f;">{{ $company->name ?? 'HyperBiz' }}</div>
                    @endif
                </td>
                <td class="company-cell">
                    <div class="company-name">{{ $company->name ?? 'HyperBiz' }}</div>
                    <div class="company-details">
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->phone)Phone: {{ $company->phone }}<br>@endif
                        @if($company->email)Email: {{ $company->email }}<br>@endif
                        @if($company->website){{ $company->website }}@endif
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
                    <div class="document-type">Purchase Order</div>
                </td>
                <td class="document-number-wrapper">
                    <div class="document-number-box">
                        <div class="document-number-label">Document No.</div>
                        <div class="document-number-value">{{ $purchaseOrder->po_number }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Content --}}
        {{-- Supplier & Order Info Cards --}}
        <div class="info-section">
            <table class="info-table">
                <tr>
                    {{-- Supplier Card --}}
                    <td class="info-card info-card-left">
                        <div class="info-header">Supplier</div>
                        <div class="info-title">{{ $purchaseOrder->supplier->client_name ?? 'N/A' }}</div>
                        <div class="info-text">
                            @if($purchaseOrder->supplier?->address)
                                <div class="info-row">{{ $purchaseOrder->supplier->address->full_address ?? '' }}</div>
                            @endif
                            @if($purchaseOrder->supplier?->client_phone_number)
                                <div class="info-row">Phone: {{ $purchaseOrder->supplier->client_phone_number }}</div>
                            @endif
                            @if($purchaseOrder->supplier?->email)
                                <div class="info-row">Email: {{ $purchaseOrder->supplier->email }}</div>
                            @endif
                            @if($purchaseOrder->supplier?->contact_person)
                                <div class="info-row">Contact: {{ $purchaseOrder->supplier->contact_person }}</div>
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
                                <td class="detail-value">{{ $purchaseOrder->order_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Expected Date</td>
                                <td class="detail-value">{{ $purchaseOrder->expected_date?->format('d M Y') ?? '-' }}</td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Status</td>
                                <td class="detail-value">
                                    <span class="badge badge-{{ $purchaseOrder->status }}">{{ strtoupper($purchaseOrder->status) }}</span>
                                </td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Payment</td>
                                <td class="detail-value">
                                    <span class="badge badge-{{ $purchaseOrder->payment_status }}">{{ strtoupper($purchaseOrder->payment_status) }}</span>
                                </td>
                            </tr>
                            <tr class="detail-row">
                                <td class="detail-label">Currency</td>
                                <td class="detail-value">{{ $purchaseOrder->currency_code ?? 'IDR' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Delivery Address --}}
        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td class="info-card" style="width: 100%;">
                        <div class="info-header">Deliver To</div>
                        <div class="info-title">{{ $company->name ?? 'HyperBiz' }}</div>
                        <div class="info-text">{{ $company->address ?? '' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Items Table --}}
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 32%;">Product</th>
                        <th style="width: 8%;" class="text-center">Qty</th>
                        <th style="width: 10%;" class="text-center">UOM</th>
                        <th style="width: 14%;" class="text-right">Unit Cost</th>
                        <th style="width: 8%;" class="text-center">Disc</th>
                        <th style="width: 14%;" class="text-right">Amount</th>
                        <th style="width: 10%;" class="text-center">Received</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $index => $item)
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
                        <td class="text-right">{{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $item->discount_percentage > 0 ? number_format($item->discount_percentage, 0) . '%' : '-' }}</td>
                        <td class="text-right font-semibold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($item->quantity_received >= $item->quantity)
                                <span style="color: #059669; font-weight: 600;">Done</span>
                            @elseif($item->quantity_received > 0)
                                <span style="color: #d97706;">{{ number_format($item->quantity_received, 0) }}/{{ number_format($item->quantity, 0) }}</span>
                            @else
                                <span style="color: #94a3b8;">-</span>
                            @endif
                        </td>
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
                        @if($purchaseOrder->notes)
                        <div class="notes-box">
                            <div class="notes-title">Notes</div>
                            <div class="notes-content">{{ $purchaseOrder->notes }}</div>
                        </div>
                        @endif
                    </td>

                    {{-- Totals --}}
                    <td class="totals-cell">
                        <table class="totals-table">
                            <tr>
                                <td class="label-cell">Subtotal</td>
                                <td class="value-cell">{{ number_format($purchaseOrder->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if($purchaseOrder->discount_amount > 0)
                            <tr>
                                <td class="label-cell">
                                    Discount
                                    @if($purchaseOrder->discount_type === 'percentage')
                                        ({{ number_format($purchaseOrder->discount_value, 0) }}%)
                                    @endif
                                </td>
                                <td class="value-cell discount-value">-{{ number_format($purchaseOrder->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($purchaseOrder->tax_enabled && $purchaseOrder->tax_amount > 0)
                            <tr>
                                <td class="label-cell">{{ $purchaseOrder->tax_name ?? 'Tax' }} ({{ number_format($purchaseOrder->tax_percentage, 0) }}%)</td>
                                <td class="value-cell">{{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="grand-total-row">
                                <td class="label-cell">Grand Total</td>
                                <td class="value-cell">{{ $purchaseOrder->currency_code ?? 'IDR' }} {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            @if($purchaseOrder->amount_paid > 0)
                            <tr>
                                <td class="label-cell">Amount Paid</td>
                                <td class="value-cell paid-value">{{ number_format($purchaseOrder->amount_paid, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="label-cell font-semibold">Balance Due</td>
                                <td class="value-cell due-value">{{ $purchaseOrder->currency_code ?? 'IDR' }} {{ number_format($purchaseOrder->grand_total - $purchaseOrder->amount_paid, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Terms & Conditions --}}
        <div class="terms-section">
            <div class="notes-box">
                <div class="notes-title">Terms & Conditions</div>
                <div class="notes-content">
                    1. Please deliver goods as per the specifications mentioned above.<br>
                    2. Include this PO number on all correspondence and invoices.<br>
                    3. Payment will be made as per agreed terms upon receipt of goods.
                </div>
            </div>
        </div>

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
                            <div class="signature-subtitle">Supplier</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
@endsection
