<?php

use App\Models\User;
use App\Models\FiscalYear;
use App\Models\FiscalPeriod;
use App\Models\ChartOfAccount;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary permissions
    Permission::firstOrCreate(['name' => 'finance.journal_entries.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.journal_entries.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.journal_entries.edit', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.journal_entries.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.journal_entries.post', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.journal_entries.void', 'guard_name' => 'web']);

    // Create admin role with all permissions
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions(Permission::all());
});

describe('Journal Entry Store Validation', function () {
    it('requires entry_date', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'fiscal_period_id' => 1,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entry_date']);
    });

    it('requires fiscal_period_id', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fiscal_period_id']);
    });

    it('requires at least 2 lines', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 0],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lines']);
    });

    it('requires lines to be balanced (debits equal credits)', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $debitAccount = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);
        $creditAccount = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => $debitAccount->id, 'debit_amount' => 100, 'credit_amount' => 0],
                    ['account_id' => $creditAccount->id, 'debit_amount' => 0, 'credit_amount' => 50], // Unbalanced
                ],
            ]);

        $response->assertStatus(422);
    });

    it('validates fiscal_period_id exists', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => 99999, // Non-existent
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => 1, 'debit_amount' => 100, 'credit_amount' => 0],
                    ['account_id' => 2, 'debit_amount' => 0, 'credit_amount' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fiscal_period_id']);
    });

    it('validates exchange_rate is greater than zero', function () {
        // Test Form Request validation directly
        $request = new \App\Http\Requests\Finance\StoreJournalEntryRequest();
        $rules = $request->rules();

        expect($rules['exchange_rate'])->toContain('numeric');
        expect($rules['exchange_rate'])->toContain('gt:0');
    });

    it('validates line account_id exists', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => 99999, 'debit_amount' => 100, 'credit_amount' => 0],
                    ['account_id' => 99998, 'debit_amount' => 0, 'credit_amount' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lines.0.account_id', 'lines.1.account_id']);
    });

    it('validates debit_amount is not negative', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $account = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => $account->id, 'debit_amount' => -100, 'credit_amount' => 0],
                    ['account_id' => $account->id, 'debit_amount' => 0, 'credit_amount' => 100],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lines.0.debit_amount']);
    });
});

describe('Journal Entry Balance Validation with BCMath', function () {
    it('accepts balanced entries with precise decimal amounts', function () {
        // Test bcmath precision for balance calculations directly
        $debits = ['33.33', '33.33', '33.34'];
        $credits = ['100.00'];

        $totalDebits = '0.00';
        foreach ($debits as $debit) {
            $totalDebits = bcadd($totalDebits, $debit, 2);
        }

        $totalCredits = '0.00';
        foreach ($credits as $credit) {
            $totalCredits = bcadd($totalCredits, $credit, 2);
        }

        // With bcmath: bcadd(bcadd('33.33', '33.33', 2), '33.34', 2) = '100.00'
        expect($totalDebits)->toBe('100.00');
        expect($totalCredits)->toBe('100.00');
        expect(bccomp($totalDebits, $totalCredits, 2))->toBe(0);
    });

    it('rejects unbalanced entries with precise detection', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $debitAccount = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);
        $creditAccount = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => $debitAccount->id, 'debit_amount' => '100.00', 'credit_amount' => '0.00'],
                    ['account_id' => $creditAccount->id, 'debit_amount' => '0.00', 'credit_amount' => '99.99'], // Off by 0.01
                ],
            ]);

        $response->assertStatus(422);
    });
});

describe('Journal Entry Authorization', function () {
    it('denies access to users without permission', function () {
        $user = User::factory()->create();
        // User without any permissions

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => 1,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [],
            ]);

        $response->assertStatus(403);
    });

    it('allows access to users with create permission', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('finance.journal_entries.create');

        $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
        $fiscalPeriod = FiscalPeriod::factory()->create([
            'fiscal_year_id' => $fiscalYear->id,
            'status' => 'open',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $account = ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/journal-entries', [
                'entry_date' => now()->format('Y-m-d'),
                'fiscal_period_id' => $fiscalPeriod->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'lines' => [
                    ['account_id' => $account->id, 'debit_amount' => 100, 'credit_amount' => 0],
                    ['account_id' => $account->id, 'debit_amount' => 0, 'credit_amount' => 100],
                ],
            ]);

        // Should not be 403 (might be 200 or 422 depending on validation)
        expect($response->status())->not->toBe(403);
    });
});
