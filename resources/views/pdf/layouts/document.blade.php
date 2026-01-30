<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Document')</title>
    <style>
        /* ============================================
           DomPDF Page Setup
           A4 = 595pt x 842pt (at 72 DPI)
           Content area = 515pt (595 - 40 - 40 margins)
           ============================================ */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #2d3748;
            background: #ffffff;
        }

        /* Outer wrapper table for margin control */
        .margin-wrapper {
            width: 100%;
            table-layout: auto;
            border-collapse: collapse;
        }

        .margin-wrapper td {
            padding: 12pt 25pt 12pt 25pt;
            vertical-align: top;
        }

        /* Main container inside the wrapper */
        .page-container {
            width: 545pt;
            max-width: 545pt;
        }

        /* Default table styles */
        table {
            border-collapse: collapse;
        }

        /* ============================================
           Header - Top accent inside container
           ============================================ */
        .header-accent {
            height: 5px;
            background-color: #1e3a5f;
            margin-bottom: 5px;
        }

        .header-main {
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 30%;
        }

        .company-logo {
            max-height: 80px;
            max-width: 150px;
            display: block;
        }

        .company-cell {
            width: 70%;
            text-align: right;
        }

        .company-name {
            font-size: 13pt;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 3px;
        }

        .company-details {
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.4;
        }

        /* ============================================
           Document Title Section
           ============================================ */
        .title-section {
            padding: 0px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
        }

        .title-table {
            width: 100%;
            border-collapse: collapse;
        }

        .document-type {
            font-size: 16pt;
            font-weight: 700;
            color: #1e3a5f;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .document-number-wrapper {
            text-align: right;
        }

        .document-number-box {
            display: inline-block;
            background: #1e3a5f;
            padding: 8px 12px;
            text-align: center;
        }

        .document-number-label {
            font-size: 6pt;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .document-number-value {
            font-size: 10pt;
            font-weight: 700;
            color: #ffffff;
        }

        /* ============================================
           Info Cards
           ============================================ */
        .info-section {
            width: 100%;
            margin-bottom: 8px;
        }

        .info-table {
            width: 100%;
            table-layout: fixed;
        }

        .info-card {
            vertical-align: top;
            padding: 8px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .info-card-left {
            width: 47%;
        }

        .info-card-right {
            width: 47%;
        }

        .info-spacer {
            width: 6%;
            border: none;
            background: transparent;
        }

        .info-header {
            font-size: 6.5pt;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 3px;
            margin-bottom: 5px;
            border-bottom: 2px solid #3182ce;
        }

        .info-title {
            font-size: 9pt;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .info-text {
            font-size: 7.5pt;
            color: #475569;
            line-height: 1.4;
        }

        /* Detail Rows - inside Order Details card */
        .detail-table {
            width: 100%;
            table-layout: auto;
        }

        .detail-row {
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-row td {
            padding: 3px 0;
            vertical-align: middle;
        }

        .detail-label {
            font-size: 7pt;
            color: #64748b;
            padding-right: 8px;
            white-space: nowrap;
        }

        .detail-value {
            font-size: 7.5pt;
            font-weight: 500;
            color: #1e293b;
            text-align: right;
            white-space: nowrap;
        }

        /* ============================================
           Items Table
           ============================================ */
        .items-section {
            margin-bottom: 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        .items-table thead tr {
            background: #1e3a5f;
        }

        .items-table th {
            padding: 7px 6px;
            font-size: 6.5pt;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .items-table th.text-center { text-align: center; }
        .items-table th.text-right { text-align: right; }

        .items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .items-table td {
            padding: 6px;
            font-size: 7.5pt;
            color: #334155;
            vertical-align: middle;
        }

        .items-table td.text-center { text-align: center; }
        .items-table td.text-right { text-align: right; }

        .item-name {
            font-weight: 600;
            color: #1e293b;
        }

        .item-sku {
            font-size: 6pt;
            color: #94a3b8;
        }

        /* ============================================
           Totals Section
           ============================================ */
        .totals-section {
            margin-bottom: 12px;
        }

        .totals-table-outer {
            width: 100%;
            border-collapse: collapse;
        }

        .notes-cell {
            width: 55%;
            vertical-align: top;
            padding-right: 12px;
        }

        .totals-cell {
            width: 45%;
            vertical-align: top;
        }

        .notes-box {
            background: #f8fafc;
            border-left: 3px solid #3182ce;
            padding: 8px 10px;
        }

        .notes-title {
            font-size: 6.5pt;
            font-weight: 700;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .notes-content {
            font-size: 7pt;
            color: #475569;
            line-height: 1.4;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        .totals-table tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 7.5pt;
        }

        .totals-table .label-cell {
            text-align: right;
            color: #64748b;
            width: 50%;
        }

        .totals-table .value-cell {
            text-align: right;
            color: #1e293b;
            font-weight: 500;
            width: 50%;
        }

        .totals-table .grand-total-row {
            background: #1e3a5f;
        }

        .totals-table .grand-total-row td {
            padding: 8px;
            color: #ffffff;
            font-weight: 700;
        }

        .totals-table .grand-total-row .value-cell {
            font-size: 10pt;
            color: #ffffff;
        }

        .discount-value { color: #dc2626 !important; }
        .paid-value { color: #059669 !important; }
        .due-value { color: #dc2626 !important; font-weight: 700 !important; }

        /* ============================================
           Status Badges
           ============================================ */
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-draft { background: #fef3c7; color: #92400e; }
        .badge-confirmed { background: #dbeafe; color: #1e40af; }
        .badge-processing { background: #e0e7ff; color: #3730a3; }
        .badge-shipped { background: #fae8ff; color: #86198f; }
        .badge-delivered, .badge-received { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        .badge-partial { background: #fef3c7; color: #92400e; }

        /* ============================================
           Signature Section
           ============================================ */
        .signature-section {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-cell {
            width: 30%;
            text-align: center;
            padding: 0 8px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            padding-top: 5px;
            margin-top: 35px;
        }

        .signature-title {
            font-size: 7pt;
            font-weight: 600;
            color: #1e293b;
        }

        .signature-subtitle {
            font-size: 6pt;
            color: #64748b;
        }

        /* ============================================
           Footer
           ============================================ */
        .footer-section {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .footer-thankyou {
            font-size: 8pt;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 2px;
        }

        .footer-company {
            font-size: 6.5pt;
            color: #94a3b8;
        }

        /* ============================================
           Watermark
           ============================================ */
        .watermark {
            position: fixed;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 60pt;
            font-weight: 700;
            color: rgba(220, 38, 38, 0.08);
            text-transform: uppercase;
            letter-spacing: 8px;
            z-index: -1;
        }

        /* Utilities */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }

        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
    @yield('styles')
</head>
<body>
    @if(isset($showWatermark) && $showWatermark)
        <div class="watermark">{{ $watermarkText ?? 'DRAFT' }}</div>
    @endif

    <table class="margin-wrapper">
        <tr>
            <td>
                <div class="page-container">
                    @yield('content')

                    <div class="footer-section">
                        <div class="footer-thankyou">Thank you for your business!</div>
                        <div class="footer-company">{{ $company->name ?? 'HyperBiz' }}@if($company->website ?? false) &bull; {{ $company->website }}@endif</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7;
            $color = array(0.6, 0.6, 0.6);
            $x = $pdf->get_width() - 70;
            $y = $pdf->get_height() - 20;
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>
</html>
