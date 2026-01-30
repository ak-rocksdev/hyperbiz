<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfitCalculationService
{
    /**
     * Get dashboard profit statistics for a date range.
     */
    public function getDashboardStats(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        // Get sales data with COGS calculation
        // Note: average_cost is in inventory_stock table, cost_price is in mst_products
        $salesData = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'so.id', '=', 'soi.sales_order_id')
            ->leftJoin('mst_products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('inventory_stock as ist', 'soi.product_id', '=', 'ist.product_id')
            ->whereBetween('so.order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                DB::raw('SUM(soi.subtotal) as revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as cogs'),
                DB::raw('COUNT(DISTINCT so.id) as order_count'),
            ])
            ->first();

        $revenue = (float) ($salesData->revenue ?? 0);
        $cogs = (float) ($salesData->cogs ?? 0);
        $grossProfit = $revenue - $cogs;
        $marginPercent = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // Get previous period for comparison
        $periodDays = $startDate->diffInDays($endDate) + 1;
        $prevStartDate = $startDate->copy()->subDays($periodDays);
        $prevEndDate = $startDate->copy()->subDay();

        $prevData = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'so.id', '=', 'soi.sales_order_id')
            ->leftJoin('mst_products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('inventory_stock as ist', 'soi.product_id', '=', 'ist.product_id')
            ->whereBetween('so.order_date', [$prevStartDate->toDateString(), $prevEndDate->toDateString()])
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                DB::raw('SUM(soi.subtotal) as revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as cogs'),
            ])
            ->first();

        $prevRevenue = (float) ($prevData->revenue ?? 0);
        $prevCogs = (float) ($prevData->cogs ?? 0);
        $prevProfit = $prevRevenue - $prevCogs;

        // Calculate trends
        $revenueTrend = $prevRevenue > 0 ? (($revenue - $prevRevenue) / $prevRevenue) * 100 : 0;
        $profitTrend = $prevProfit > 0 ? (($grossProfit - $prevProfit) / $prevProfit) * 100 : 0;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'margin_percent' => round($marginPercent, 1),
            'order_count' => $salesData->order_count ?? 0,
            'revenue_trend' => round($revenueTrend, 1),
            'profit_trend' => round($profitTrend, 1),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get profit trend data for chart (daily data).
     */
    public function getProfitTrend(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dailyData = DB::table('sales_orders as so')
            ->join('sales_order_items as soi', 'so.id', '=', 'soi.sales_order_id')
            ->leftJoin('mst_products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('inventory_stock as ist', 'soi.product_id', '=', 'ist.product_id')
            ->whereBetween('so.order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                DB::raw('DATE(so.order_date) as date'),
                DB::raw('SUM(soi.subtotal) as revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as cogs'),
            ])
            ->groupBy(DB::raw('DATE(so.order_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill in missing days with zeros
        $result = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dateStr = $current->toDateString();
            $dayData = $dailyData->get($dateStr);

            $revenue = (float) ($dayData->revenue ?? 0);
            $cogs = (float) ($dayData->cogs ?? 0);

            $result[] = [
                'date' => $dateStr,
                'date_label' => $current->format('d M'),
                'revenue' => $revenue,
                'cogs' => $cogs,
                'profit' => $revenue - $cogs,
            ];

            $current->addDay();
        }

        return $result;
    }

    /**
     * Get top profitable products.
     */
    public function getTopProfitableProducts(int $limit = 5, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $products = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'soi.sales_order_id', '=', 'so.id')
            ->join('mst_products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('inventory_stock as ist', 'soi.product_id', '=', 'ist.product_id')
            ->whereBetween('so.order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                DB::raw('SUM(soi.quantity) as units_sold'),
                DB::raw('SUM(soi.subtotal) as revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as cogs'),
                DB::raw('SUM(soi.subtotal) - SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as profit'),
            ])
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->orderByDesc('profit')
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            $revenue = (float) $product->revenue;
            $profit = (float) $product->profit;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'units_sold' => (float) $product->units_sold,
                'revenue' => $revenue,
                'profit' => $profit,
                'margin_percent' => round($margin, 1),
            ];
        })->toArray();
    }

    /**
     * Get least profitable (or loss-making) products.
     */
    public function getLeastProfitableProducts(int $limit = 5, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $products = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'soi.sales_order_id', '=', 'so.id')
            ->join('mst_products as p', 'soi.product_id', '=', 'p.id')
            ->leftJoin('inventory_stock as ist', 'soi.product_id', '=', 'ist.product_id')
            ->whereBetween('so.order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                DB::raw('SUM(soi.quantity) as units_sold'),
                DB::raw('SUM(soi.subtotal) as revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as cogs'),
                DB::raw('SUM(soi.subtotal) - SUM(soi.quantity * COALESCE(soi.unit_cost, ist.average_cost, p.cost_price, 0)) as profit'),
            ])
            ->groupBy('p.id', 'p.name', 'p.sku')
            ->orderBy('profit')
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            $revenue = (float) $product->revenue;
            $profit = (float) $product->profit;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'units_sold' => (float) $product->units_sold,
                'revenue' => $revenue,
                'profit' => $profit,
                'margin_percent' => round($margin, 1),
            ];
        })->toArray();
    }

    /**
     * Get profit breakdown for a specific sales order.
     */
    public function getSalesOrderProfit(int $salesOrderId): array
    {
        $order = SalesOrder::with(['items.product.inventoryStock'])->findOrFail($salesOrderId);

        $itemsProfit = $order->items->map(function ($item) {
            $revenue = (float) $item->subtotal;
            // Priority: unit_cost (from SO item) > average_cost (from inventory) > cost_price (from product)
            $unitCost = (float) ($item->unit_cost ?? $item->product->inventoryStock?->average_cost ?? $item->product->cost_price ?? 0);
            $cogs = $item->quantity * $unitCost;
            $profit = $revenue - $cogs;
            $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? 'Unknown',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'unit_cost' => $unitCost,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'profit' => $profit,
                'margin_percent' => round($margin, 1),
            ];
        });

        $totalRevenue = (float) ($order->subtotal - $order->discount_amount);
        $totalCogs = $itemsProfit->sum('cogs');
        $totalProfit = $totalRevenue - $totalCogs;
        $totalMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        return [
            'order_id' => $order->id,
            'so_number' => $order->so_number,
            'order_date' => $order->order_date->toDateString(),
            'revenue' => $totalRevenue,
            'cogs' => $totalCogs,
            'gross_profit' => $totalProfit,
            'margin_percent' => round($totalMargin, 1),
            'items' => $itemsProfit->toArray(),
        ];
    }

    /**
     * Get product profit metrics.
     */
    public function getProductProfitMetrics(int $productId): array
    {
        $product = Product::with('inventoryStock')->findOrFail($productId);

        // All-time stats
        $stats = DB::table('sales_order_items as soi')
            ->join('sales_orders as so', 'soi.sales_order_id', '=', 'so.id')
            ->where('soi.product_id', $productId)
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->select([
                DB::raw('SUM(soi.quantity) as units_sold'),
                DB::raw('SUM(soi.subtotal) as total_revenue'),
                DB::raw('SUM(soi.quantity * COALESCE(soi.unit_cost, 0)) as total_cogs'),
            ])
            ->first();

        $unitsSold = (float) ($stats->units_sold ?? 0);
        $totalRevenue = (float) ($stats->total_revenue ?? 0);
        $totalCogs = (float) ($stats->total_cogs ?? 0);
        $totalProfit = $totalRevenue - $totalCogs;

        // Get average cost from inventory_stock or fallback to cost_price
        $avgCost = (float) ($product->inventoryStock?->average_cost ?? $product->cost_price ?? 0);

        // Current margin based on current pricing
        $currentMargin = 0;
        if ($product->price > 0 && $avgCost > 0) {
            $currentMargin = (($product->price - $avgCost) / $product->price) * 100;
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'selling_price' => (float) $product->price,
            'average_cost' => $avgCost,
            'current_margin_percent' => round($currentMargin, 1),
            'units_sold' => $unitsSold,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'profit_per_unit' => $unitsSold > 0 ? round($totalProfit / $unitsSold, 2) : 0,
        ];
    }
}
