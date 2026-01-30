<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Company;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class PurchaseOrderPdfController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchase-orders.view'),
        ];
    }

    /**
     * Preview PDF in browser (inline display).
     */
    public function preview(PurchaseOrder $purchaseOrder)
    {
        $data = $this->preparePdfData($purchaseOrder);

        $pdf = Pdf::loadView('pdf.purchase-order', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->stream("purchase-order-{$purchaseOrder->po_number}.pdf");
    }

    /**
     * Download PDF file.
     */
    public function download(PurchaseOrder $purchaseOrder)
    {
        $data = $this->preparePdfData($purchaseOrder);

        $pdf = Pdf::loadView('pdf.purchase-order', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return $pdf->download("purchase-order-{$purchaseOrder->po_number}.pdf");
    }

    /**
     * Prepare data for PDF generation.
     */
    private function preparePdfData(PurchaseOrder $purchaseOrder): array
    {
        // Load relationships
        $purchaseOrder->load([
            'supplier.address',
            'items.product',
            'items.uom',
            'createdBy',
        ]);

        // Get company info
        $company = Company::first();

        return [
            'purchaseOrder' => $purchaseOrder,
            'company' => $company,
            'preparedBy' => $purchaseOrder->createdBy?->name ?? 'System',
        ];
    }
}
