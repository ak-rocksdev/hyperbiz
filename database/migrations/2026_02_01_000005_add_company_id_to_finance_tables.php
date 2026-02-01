<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance tables that need company_id for multi-tenancy.
     */
    private array $tables = [
        'fin_settings',
        'fin_chart_of_accounts',
        'fin_fiscal_years',
        'fin_fiscal_periods',
        'fin_expenses',
        'fin_expense_attachments',
        'fin_journal_entries',
        'fin_journal_entry_lines',
        'fin_account_balances',
        'fin_supplier_balances',
        'fin_bank_accounts',
        'fin_bank_reconciliations',
        'fin_bank_transactions',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('company_id')->nullable()->after('id')->constrained('mst_company')->nullOnDelete();
                    $table->index('company_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'company_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['company_id']);
                    $table->dropColumn('company_id');
                });
            }
        }
    }
};
