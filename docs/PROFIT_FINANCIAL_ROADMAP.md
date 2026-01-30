# Profit & Financial Features Roadmap

> **Document Purpose:** Development guide for implementing profit visibility and financial features in HyperBiz ERP.
>
> **Last Updated:** January 2026

---

## Overview

This document outlines the phased approach to implementing profit/loss visibility and financial features. The strategy prioritizes **immediate business value** by leveraging existing data before building complex financial infrastructure.

### Key Principle
**Gross Profit First, Full Financials Later**

- Gross Profit can be calculated from existing data (sales, costs)
- Net Profit requires additional financial module (expenses tracking)
- Business owners need Gross Profit insights for daily decisions

---

## Data Foundation (Already Available)

| Data | Source Table | Field(s) |
|------|--------------|----------|
| Sales Revenue | `trx_sales_orders` + `trx_sales_order_items` | `unit_price × quantity` |
| Cost of Goods Sold | `mst_products` | `average_cost` |
| Per-unit Cost | `inventory_movements` | `unit_cost` |
| Product Base Cost | `mst_products` | `cost_price` |

### Profit Calculation Formula
```
Gross Profit = Revenue - COGS
Gross Margin % = (Gross Profit / Revenue) × 100

Where:
- Revenue = SUM(sales_order_items.unit_price × quantity)
- COGS = SUM(sales_order_items.quantity × product.average_cost)
```

---

## Permission Structure

### New Permissions to Add

```php
// Profit & Financial Permissions
'reports.profit.view',           // View profit summary/dashboard widgets
'reports.profit.detailed',       // View detailed cost breakdown
'products.view_cost',            // See product cost prices & margins
'orders.view_profit',            // See per-order profit calculation
'customers.view_profitability',  // See customer profit analysis
'dashboard.financial_widgets',   // See financial widgets on dashboard

// Future: Full Financial Module
'expenses.view',                 // View expenses
'expenses.create',               // Create/edit expenses
'expenses.delete',               // Delete expenses
'reports.pnl.view',              // View P&L statement
'reports.pnl.export',            // Export P&L reports
```

### Role Mapping

| Role | Permissions |
|------|-------------|
| **Superadmin** | All permissions |
| **Admin/Manager** | `reports.profit.view`, `products.view_cost`, `orders.view_profit`, `dashboard.financial_widgets` |
| **Accountant** | All profit permissions + `expenses.*` + `reports.pnl.*` |
| **Sales Staff** | None (revenue only, no cost/profit visibility) |

---

## Implementation Phases

---

## Phase 1: Profit Visibility Dashboard (Priority: HIGH)

**Timeline:** 1-2 weeks
**Complexity:** Low
**Dependencies:** None (uses existing data)

### Checklist

#### 1.1 Backend - Permissions
- [ ] Add permissions to `RolesPermissionSeeder.php`:
  - `reports.profit.view`
  - `dashboard.financial_widgets`
  - `orders.view_profit`
  - `products.view_cost`
- [ ] Update role assignments (admin gets profit permissions)
- [ ] Run seeder to apply permissions

#### 1.2 Backend - Profit Calculation Service
- [ ] Create `app/Services/ProfitCalculationService.php`
- [ ] Implement methods:
  - `getDashboardStats(startDate, endDate)` - Revenue, COGS, Gross Profit, Margin %
  - `getProfitTrend(days)` - Daily profit for chart
  - `getTopProfitableProducts(limit)` - Products ranked by profit
  - `getSalesOrderProfit(salesOrderId)` - Per-order profit breakdown
- [ ] Add caching for expensive calculations (optional)

#### 1.3 Backend - Dashboard Controller Updates
- [ ] Update `DashboardController` to include profit stats
- [ ] Gate profit data behind `dashboard.financial_widgets` permission
- [ ] Return profit data only if user has permission

#### 1.4 Frontend - Dashboard Widgets
- [ ] Create profit stats cards:
  - Total Revenue (with trend indicator)
  - Total COGS
  - Gross Profit (with trend indicator)
  - Profit Margin %
- [ ] Create profit trend line chart (last 30 days)
- [ ] Create "Top 5 Profitable Products" mini-table
- [ ] All widgets permission-gated (v-if)
- [ ] Responsive design (mobile-friendly)

#### 1.5 Frontend - Sales Order Profit View
- [ ] Update Sales Order detail page
- [ ] Add profit breakdown section (permission-gated):
  - Revenue (subtotal)
  - COGS (calculated)
  - Gross Profit
  - Margin %
- [ ] Per-item cost display in items table (optional)

#### 1.6 Testing
- [ ] Test calculations match expected values
- [ ] Test permission gating (staff shouldn't see profit)
- [ ] Test with zero sales (handle division by zero)
- [ ] Test performance with large datasets

---

## Phase 2: Product & Order Profit Details (Priority: MEDIUM)

**Timeline:** 1 week
**Complexity:** Low
**Dependencies:** Phase 1

### Checklist

#### 2.1 Product Detail Page
- [ ] Add profit metrics section (permission-gated):
  - Current margin (selling price vs avg cost)
  - Total units sold (all time)
  - Total revenue generated
  - Total profit generated
  - Profit per unit
- [ ] Margin health indicator (color-coded)
- [ ] Price suggestion based on target margin (optional)

#### 2.2 Product List Page
- [ ] Add optional "Margin" column (permission-gated)
- [ ] Color-code margins (green >30%, yellow 15-30%, red <15%)

#### 2.3 Order Items Enhancement
- [ ] Show unit cost alongside unit price (permission-gated)
- [ ] Show line-item profit in orders

---

## Phase 3: Profit Reports Page (Priority: MEDIUM)

**Timeline:** 2 weeks
**Complexity:** Medium
**Dependencies:** Phase 1

### Checklist

#### 3.1 Create Reports Section
- [ ] Create `/reports` route group
- [ ] Create `ReportsController`
- [ ] Add sidebar menu item "Reports" with sub-items

#### 3.2 Profit & Loss Report Page
- [ ] Create `/reports/profit-loss` page
- [ ] Filters:
  - Date range picker
  - Compare periods (this month vs last month)
  - Category filter
  - Customer filter
- [ ] Summary cards (same as dashboard but for selected period)
- [ ] P&L table format:
  ```
  Revenue
    - Product Sales: Rp XXX

  Cost of Goods Sold
    - Product Costs: Rp XXX

  Gross Profit: Rp XXX
  Gross Margin: XX%
  ```

#### 3.3 Product Profitability Report
- [ ] Create `/reports/product-profitability` page
- [ ] Table: Product, Revenue, COGS, Profit, Margin %, Units Sold
- [ ] Sortable columns
- [ ] Export to Excel/PDF

#### 3.4 Customer Profitability Report
- [ ] Create `/reports/customer-profitability` page
- [ ] Table: Customer, Orders, Revenue, Profit, Avg Margin, LTV
- [ ] Identify top/bottom customers by profit

---

## Phase 4: Financial Module - Expenses (Priority: LOW)

**Timeline:** 3-4 weeks
**Complexity:** High
**Dependencies:** Phase 1-3 (optional, can be done independently)

### Checklist

#### 4.1 Database Schema
- [ ] Create `expense_categories` table:
  ```
  id, name, type (fixed/variable), description, is_active
  ```
- [ ] Create `expenses` table:
  ```
  id, category_id, amount, expense_date, description,
  receipt_path, created_by, created_at
  ```
- [ ] Create `recurring_expenses` table (optional):
  ```
  id, category_id, amount, frequency (monthly/weekly),
  start_date, end_date, is_active
  ```
- [ ] Run migrations

#### 4.2 Seed Default Categories
- [ ] Rent/Lease
- [ ] Utilities (Electricity, Water, Internet)
- [ ] Salaries & Wages
- [ ] Marketing & Advertising
- [ ] Office Supplies
- [ ] Transportation/Delivery
- [ ] Insurance
- [ ] Maintenance & Repairs
- [ ] Professional Services
- [ ] Miscellaneous

#### 4.3 Backend - Expense CRUD
- [ ] Create `ExpenseController`
- [ ] Create `ExpenseCategoryController`
- [ ] API endpoints:
  - GET/POST `/expenses`
  - GET/PUT/DELETE `/expenses/{id}`
  - GET `/expense-categories`
- [ ] Validation rules
- [ ] Receipt file upload

#### 4.4 Frontend - Expense Management
- [ ] Create `/expenses` list page
- [ ] Create expense form (create/edit)
- [ ] Category management page
- [ ] Expense summary by category (pie chart)
- [ ] Monthly expense trend

#### 4.5 Net Profit Calculation
- [ ] Update `ProfitCalculationService`:
  - Add `getOperatingExpenses(startDate, endDate)`
  - Add `getNetProfit(startDate, endDate)`
- [ ] Update dashboard to show Net Profit (if expenses exist)
- [ ] Update P&L report to include expenses section

---

## Phase 5: Advanced Financial (Priority: FUTURE)

**Timeline:** 4-6 weeks
**Complexity:** Very High
**Dependencies:** Phase 4

### Checklist (High-Level)

#### 5.1 Chart of Accounts
- [ ] Design account structure (Assets, Liabilities, Equity, Revenue, Expenses)
- [ ] Create `chart_of_accounts` table
- [ ] Seed default accounts
- [ ] Account management UI

#### 5.2 Journal Entries
- [ ] Create `journal_entries` table
- [ ] Create `journal_entry_lines` table
- [ ] Double-entry validation
- [ ] Auto-generate entries from sales/purchases

#### 5.3 Financial Statements
- [ ] Balance Sheet report
- [ ] Cash Flow Statement
- [ ] Trial Balance

#### 5.4 Budgeting
- [ ] Budget creation by category/period
- [ ] Budget vs Actual comparison
- [ ] Variance analysis

---

## UI/UX Guidelines

### Dashboard Financial Widgets Design

```
┌─────────────────────────────────────────────────────────────────────┐
│  Financial Overview                              [This Month ▼]     │
├─────────────┬─────────────┬─────────────┬─────────────┬────────────┤
│   Revenue   │    COGS     │Gross Profit │   Margin    │  vs Last   │
│  Rp 150M    │   Rp 95M    │   Rp 55M    │   36.7%     │   ↑ 12%    │
│  ↑ 8% MTD   │             │   ↑ 5% MTD  │             │            │
└─────────────┴─────────────┴─────────────┴─────────────┴────────────┘

┌─────────────────────────────────┬───────────────────────────────────┐
│  Profit Trend (Last 30 Days)   │   Top Profitable Products         │
│  ┌──────────────────────────┐  │   ┌─────────────────────────────┐ │
│  │    📈 Line Chart         │  │   │ 1. Product A    Rp 12.5M    │ │
│  │                          │  │   │ 2. Product B    Rp 8.2M     │ │
│  │                          │  │   │ 3. Product C    Rp 6.1M     │ │
│  │                          │  │   │ 4. Product D    Rp 4.8M     │ │
│  └──────────────────────────┘  │   │ 5. Product E    Rp 3.9M     │ │
└─────────────────────────────────┴───────────────────────────────────┘
```

### Color Coding for Margins
- **Green (Success):** Margin > 30%
- **Yellow (Warning):** Margin 15-30%
- **Red (Danger):** Margin < 15%
- **Gray:** No data / Division by zero

### Permission-Gated Display
```vue
<template>
  <!-- Only show to users with permission -->
  <div v-if="hasPermission('dashboard.financial_widgets')">
    <ProfitWidgets :stats="profitStats" />
  </div>
</template>
```

---

## Technical Notes

### Performance Considerations
1. **Caching:** Cache daily profit calculations, invalidate on new sales
2. **Aggregation:** Use database aggregation, not PHP loops
3. **Indexing:** Ensure proper indexes on date columns
4. **Pagination:** Paginate product/customer profitability reports

### Edge Cases to Handle
1. **Zero Revenue:** Avoid division by zero in margin calculation
2. **Negative Margins:** Products sold below cost (display in red)
3. **Missing Costs:** Products without average_cost set
4. **Refunds/Returns:** Adjust profit calculations for returned items
5. **Partial Shipments:** Calculate profit on shipped qty only

### Sample Service Method
```php
// app/Services/ProfitCalculationService.php

public function getDashboardStats(Carbon $startDate, Carbon $endDate): array
{
    $salesData = DB::table('trx_sales_orders as so')
        ->join('trx_sales_order_items as soi', 'so.id', '=', 'soi.sales_order_id')
        ->join('mst_products as p', 'soi.product_id', '=', 'p.id')
        ->whereBetween('so.order_date', [$startDate, $endDate])
        ->where('so.status', '!=', 'cancelled')
        ->select([
            DB::raw('SUM(soi.quantity * soi.unit_price) as revenue'),
            DB::raw('SUM(soi.quantity * COALESCE(p.average_cost, 0)) as cogs'),
        ])
        ->first();

    $revenue = $salesData->revenue ?? 0;
    $cogs = $salesData->cogs ?? 0;
    $grossProfit = $revenue - $cogs;
    $marginPercent = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

    return [
        'revenue' => $revenue,
        'cogs' => $cogs,
        'gross_profit' => $grossProfit,
        'margin_percent' => round($marginPercent, 2),
    ];
}
```

---

## References

- Metronic 9 Dashboard Examples
- Laravel Query Builder Aggregations
- Vue.js Chart Libraries (Chart.js / ApexCharts)

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| Jan 2026 | 1.0 | Initial roadmap created |

