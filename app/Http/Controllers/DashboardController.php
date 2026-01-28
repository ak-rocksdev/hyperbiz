<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\Product;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\InventoryStock;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get greeting based on server time
        $hour = now()->hour;
        if ($hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour < 17) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        $stats = [
            'greeting' => $greeting,
            // Existing counts
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_customers' => Customer::count(),
            'total_transactions' => Transaction::count(),
            'total_products' => Product::count(),
            'total_brands' => Brand::count(),
            'total_categories' => ProductCategory::count(),

            // Financial KPIs (Month to Date)
            'revenue_mtd' => $this->getRevenueMTD(),
            'expenses_mtd' => $this->getExpensesMTD(),
            'outstanding_receivables' => $this->getOutstandingReceivables(),
            'outstanding_payables' => $this->getOutstandingPayables(),

            // Inventory KPIs
            'low_stock_count' => $this->getLowStockCount(),
            'out_of_stock_count' => $this->getOutOfStockCount(),
            'inventory_value' => $this->getInventoryValue(),
        ];

        $charts = [
            'sales_trend' => $this->getSalesTrend(30),
            'top_customers' => $this->getTopCustomers(5),
            'order_status' => $this->getOrderStatusDistribution(),
            'stock_movement' => $this->getStockMovementTrend(30),
        ];

        $recentActivity = [
            'recent_sales_orders' => $this->getRecentSalesOrders(5),
            'recent_purchase_orders' => $this->getRecentPurchaseOrders(5),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'charts' => $charts,
            'recentActivity' => $recentActivity,
        ]);
    }

    // ==================== Financial KPIs ====================

    private function getRevenueMTD(): float
    {
        return SalesOrder::where('status', '!=', 'cancelled')
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('grand_total') ?? 0;
    }

    private function getExpensesMTD(): float
    {
        return PurchaseOrder::where('status', '!=', 'cancelled')
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('grand_total') ?? 0;
    }

    private function getOutstandingReceivables(): float
    {
        return SalesOrder::where('status', '!=', 'cancelled')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('SUM(grand_total - amount_paid) as total')
            ->value('total') ?? 0;
    }

    private function getOutstandingPayables(): float
    {
        return PurchaseOrder::where('status', '!=', 'cancelled')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('SUM(grand_total - amount_paid) as total')
            ->value('total') ?? 0;
    }

    // ==================== Inventory KPIs ====================

    private function getLowStockCount(): int
    {
        return InventoryStock::whereRaw('reorder_level > 0 AND quantity_on_hand > 0 AND quantity_on_hand <= reorder_level')
            ->count();
    }

    private function getOutOfStockCount(): int
    {
        return InventoryStock::where('quantity_on_hand', '<=', 0)->count();
    }

    private function getInventoryValue(): float
    {
        return InventoryStock::selectRaw('SUM(quantity_on_hand * average_cost) as total')
            ->value('total') ?? 0;
    }

    // ==================== Chart Data ====================

    private function getSalesTrend(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        // Get sales data grouped by date
        $salesData = SalesOrder::where('status', '!=', 'cancelled')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('DATE(order_date) as date, SUM(grand_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill in missing dates with 0
        $labels = [];
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d M');
            $data[] = $salesData[$dateStr] ?? 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getTopCustomers(int $limit = 5): array
    {
        $customers = SalesOrder::where('status', '!=', 'cancelled')
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->whereNotNull('customer_id')
            ->join('mst_client', 'sales_orders.customer_id', '=', 'mst_client.id')
            ->selectRaw('mst_client.client_name as name, SUM(sales_orders.grand_total) as total')
            ->groupBy('sales_orders.customer_id', 'mst_client.client_name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return [
            'labels' => $customers->pluck('name')->toArray(),
            'data' => $customers->pluck('total')->toArray(),
        ];
    }

    private function getOrderStatusDistribution(): array
    {
        $statusLabels = [
            'draft' => 'Draft',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'partial' => 'Partial',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ];

        $statusColors = [
            'draft' => '#6B7280',      // Gray
            'confirmed' => '#3B82F6',  // Blue
            'processing' => '#F59E0B', // Orange
            'partial' => '#8B5CF6',    // Purple
            'shipped' => '#06B6D4',    // Cyan
            'delivered' => '#22C55E',  // Green
            'cancelled' => '#EF4444',  // Red
        ];

        $distribution = SalesOrder::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($statusLabels as $status => $label) {
            if (isset($distribution[$status]) && $distribution[$status] > 0) {
                $labels[] = $label;
                $data[] = $distribution[$status];
                $colors[] = $statusColors[$status];
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    private function getStockMovementTrend(int $days = 30): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        $endDate = now()->endOfDay();

        $inTypes = ['purchase_in', 'sales_return', 'adjustment_in', 'opening_stock'];
        $outTypes = ['sales_out', 'purchase_return', 'adjustment_out'];

        // Get stock in data
        $stockInData = InventoryMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->whereIn('movement_type', $inTypes)
            ->selectRaw('DATE(movement_date) as date, SUM(ABS(quantity)) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Get stock out data
        $stockOutData = InventoryMovement::whereBetween('movement_date', [$startDate, $endDate])
            ->whereIn('movement_type', $outTypes)
            ->selectRaw('DATE(movement_date) as date, SUM(ABS(quantity)) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill in missing dates
        $labels = [];
        $stockIn = [];
        $stockOut = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d M');
            $stockIn[] = $stockInData[$dateStr] ?? 0;
            $stockOut[] = $stockOutData[$dateStr] ?? 0;
            $currentDate->addDay();
        }

        return [
            'labels' => $labels,
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
        ];
    }

    // ==================== Recent Activity ====================

    private function getRecentSalesOrders(int $limit = 5): array
    {
        return SalesOrder::with('customer')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($so) => [
                'id' => $so->id,
                'so_number' => $so->so_number,
                'customer_name' => $so->customer?->client_name ?? 'N/A',
                'grand_total' => $so->grand_total,
                'status' => $so->status,
                'payment_status' => $so->payment_status,
                'order_date' => Carbon::parse($so->order_date)->format('d M Y'),
            ])
            ->toArray();
    }

    private function getRecentPurchaseOrders(int $limit = 5): array
    {
        return PurchaseOrder::with('supplier')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($po) => [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'supplier_name' => $po->supplier?->client_name ?? 'N/A',
                'grand_total' => $po->grand_total,
                'status' => $po->status,
                'payment_status' => $po->payment_status,
                'order_date' => Carbon::parse($po->order_date)->format('d M Y'),
            ])
            ->toArray();
    }
}
