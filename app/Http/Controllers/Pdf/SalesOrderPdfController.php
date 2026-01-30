<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Company;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SalesOrderPdfController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:sales-orders.view'),
        ];
    }

    /**
     * Preview PDF in browser (inline display).
     */
    public function preview(SalesOrder $salesOrder)
    {
        $data = $this->preparePdfData($salesOrder);

        $pdf = Pdf::loadView('pdf.sales-order', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->stream("sales-order-{$salesOrder->so_number}.pdf");
    }

    /**
     * Download PDF file.
     */
    public function download(SalesOrder $salesOrder)
    {
        $data = $this->preparePdfData($salesOrder);

        $pdf = Pdf::loadView('pdf.sales-order', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download("sales-order-{$salesOrder->so_number}.pdf");
    }

    /**
     * Prepare data for PDF generation.
     */
    private function preparePdfData(SalesOrder $salesOrder): array
    {
        // Load relationships
        $salesOrder->load([
            'customer.address',
            'items.product',
            'items.uom',
            'createdBy',
        ]);

        // Get company info
        $company = Company::first();

        // Get bank details from settings (if configured)
        $bankDetails = null;
        if ($company) {
            $settings = CompanySetting::getAllForCompany($company->id);
            if (!empty($settings['bank_name']) || !empty($settings['bank_account_number'])) {
                $bankDetails = [
                    'bank_name' => $settings['bank_name'] ?? null,
                    'account_number' => $settings['bank_account_number'] ?? null,
                    'account_name' => $settings['bank_account_name'] ?? null,
                ];
            }
        }

        return [
            'salesOrder' => $salesOrder,
            'company' => $company,
            'bankDetails' => $bankDetails,
            'preparedBy' => $salesOrder->createdBy?->name ?? 'System',
        ];
    }
}
