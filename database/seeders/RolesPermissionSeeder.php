<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions using dot notation: {module}.{action}
        $permissions = [
            // Roles management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',

            // Users management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Customers management
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Transactions management
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'transactions.delete',

            // Products management
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            // Brands management
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            // Product Categories management
            'product-categories.view',
            'product-categories.create',
            'product-categories.edit',
            'product-categories.delete',

            // Units of Measure (UoM) management
            'uom.view',
            'uom.create',
            'uom.edit',
            'uom.delete',

            // Company management
            'company.view',
            'company.edit',

            // System Logs
            'logs.view',

            // Purchase Orders management
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.delete',

            // Sales Orders management
            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.edit',
            'sales-orders.delete',

            // Payments management
            'payments.view',
            'payments.create',
            'payments.edit',

            // Inventory management
            'inventory.view',
            'inventory.edit',
            'inventory.adjustments.view',
            'inventory.adjustments.create',

            // Profit & Financial Reports
            'dashboard.financial_widgets',   // See financial widgets on dashboard
            'reports.profit.view',           // View profit summary/reports
            'reports.profit.detailed',       // View detailed cost breakdown
            'products.view_cost',            // See product cost prices & margins
            'orders.view_profit',            // See per-order profit calculation
            'customers.view_profitability',  // See customer profit analysis

            // Finance Module
            'finance.settings.view',
            'finance.settings.manage',
            'finance.chart_of_accounts.view',
            'finance.chart_of_accounts.manage',
            'finance.fiscal_periods.view',
            'finance.fiscal_periods.close',
            'finance.journal_entries.view',
            'finance.journal_entries.create',
            'finance.journal_entries.post',
            'finance.journal_entries.void',
            'finance.expenses.view',
            'finance.expenses.create',
            'finance.expenses.edit',
            'finance.expenses.delete',
            'finance.expenses.approve',
            'finance.bank.view',
            'finance.bank.manage',
            'finance.bank.reconcile',
            'finance.reports.trial_balance',
            'finance.reports.profit_loss',
            'finance.reports.balance_sheet',
            'finance.reports.ar_aging',
            'finance.reports.ap_aging',
            'finance.tax.view',
            'finance.tax.manage',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create superadmin role (will bypass all permissions via Gate::before)
        $roleSuperadmin = Role::firstOrCreate(['name' => 'superadmin']);

        // Create admin role with specific permissions
        $roleAdmin = Role::firstOrCreate(['name' => 'admin']);
        $roleAdmin->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
            'customers.view',
            'customers.create',
            'customers.edit',
            'transactions.view',
            'transactions.create',
            'transactions.edit',
            'products.view',
            'products.create',
            'products.edit',
            'brands.view',
            'brands.create',
            'brands.edit',
            'product-categories.view',
            'product-categories.create',
            'product-categories.edit',
            // Units of Measure
            'uom.view',
            'uom.create',
            'uom.edit',
            'company.view',
            // Purchase Orders
            'purchase-orders.view',
            'purchase-orders.create',
            'purchase-orders.edit',
            // Sales Orders
            'sales-orders.view',
            'sales-orders.create',
            'sales-orders.edit',
            // Payments
            'payments.view',
            'payments.create',
            // Inventory
            'inventory.view',
            'inventory.edit',
            'inventory.adjustments.view',
            'inventory.adjustments.create',
            // Profit permissions for admin
            'dashboard.financial_widgets',
            'reports.profit.view',
            'products.view_cost',
            'orders.view_profit',
            // Finance permissions for admin
            'finance.settings.view',
            'finance.chart_of_accounts.view',
            'finance.fiscal_periods.view',
            'finance.journal_entries.view',
            'finance.expenses.view',
            'finance.bank.view',
            'finance.reports.trial_balance',
            'finance.reports.profit_loss',
            'finance.reports.balance_sheet',
            'finance.reports.ar_aging',
            'finance.reports.ap_aging',
        ]);

        // Create staff role with limited permissions
        $roleStaff = Role::firstOrCreate(['name' => 'staff']);
        $roleStaff->syncPermissions([
            'customers.view',
            'transactions.view',
            'transactions.create',
            'products.view',
            'brands.view',
            'product-categories.view',
            // Units of Measure (view only)
            'uom.view',
            // Purchase Orders (view only)
            'purchase-orders.view',
            // Sales Orders (view and create)
            'sales-orders.view',
            'sales-orders.create',
            // Payments (view only)
            'payments.view',
            // Inventory (view and adjustments)
            'inventory.view',
            'inventory.adjustments.view',
        ]);

        // Assign superadmin role to user ID 1 (if exists)
        $user1 = User::find(1);
        if ($user1) {
            $user1->assignRole($roleSuperadmin);
        }

        // Assign admin role to user ID 2 (if exists)
        $user2 = User::find(2);
        if ($user2) {
            $user2->assignRole($roleAdmin);
        }
    }
}
