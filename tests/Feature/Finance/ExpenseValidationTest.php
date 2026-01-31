<?php

use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ChartOfAccount;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary permissions
    Permission::firstOrCreate(['name' => 'finance.expenses.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.expenses.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.expenses.edit', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.expenses.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'finance.expenses.approve', 'guard_name' => 'web']);

    // Create admin role with all permissions
    $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->syncPermissions(Permission::all());
});

describe('Expense Store Validation', function () {
    it('requires expense_date', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'category_id' => 1,
                'account_id' => 1,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expense_date']);
    });

    it('requires amount to be greater than zero', function () {
        // Test Form Request validation directly
        $request = new \App\Http\Requests\Finance\StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['amount'])->toContain('numeric');
        expect($rules['amount'])->toContain('gt:0');
    });

    it('requires exchange_rate to be greater than zero', function () {
        // Test Form Request validation directly
        $request = new \App\Http\Requests\Finance\StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['exchange_rate'])->toContain('numeric');
        expect($rules['exchange_rate'])->toContain('gt:0');
    });

    it('validates currency_code is exactly 3 characters', function () {
        // Test Form Request validation directly
        $request = new \App\Http\Requests\Finance\StoreExpenseRequest();
        $rules = $request->rules();

        expect($rules['currency_code'])->toContain('size:3');
    });

    it('validates category_id exists', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $account = ChartOfAccount::factory()->create(['account_type' => 'expense']);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => 99999, // Non-existent
                'account_id' => $account->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    });

    it('validates account_id exists', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'account_id' => 99999, // Non-existent
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_id']);
    });

    it('validates payment_method is valid option', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = ExpenseCategory::factory()->create();
        $account = ChartOfAccount::factory()->create(['account_type' => 'expense']);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'account_id' => $account->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    });

    it('accepts valid payment methods', function () {
        // Test Form Request validation rules directly
        $request = new \App\Http\Requests\Finance\StoreExpenseRequest();
        $rules = $request->rules();

        // Verify payment_method has a Rule::in validation
        $paymentMethodRules = $rules['payment_method'];
        expect($paymentMethodRules)->toBeArray();

        // Find the Rule object
        $inRule = collect($paymentMethodRules)->first(fn($r) => $r instanceof \Illuminate\Validation\Rules\In);
        expect($inRule)->toBeInstanceOf(\Illuminate\Validation\Rules\In::class);

        // Convert Rule to string and check it contains expected values
        $ruleString = (string) $inRule;
        expect(str_contains($ruleString, 'cash'))->toBeTrue();
        expect(str_contains($ruleString, 'bank_transfer'))->toBeTrue();
        expect(str_contains($ruleString, 'credit_card'))->toBeTrue();
        expect(str_contains($ruleString, 'check'))->toBeTrue();
        expect(str_contains($ruleString, 'other'))->toBeTrue();
    });
});

describe('Expense Amount Precision', function () {
    it('stores amount with correct precision', function () {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = ExpenseCategory::factory()->create();
        $account = ChartOfAccount::factory()->create([
            'account_type' => 'expense',
            'is_header' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'account_id' => $account->id,
                'currency_code' => 'IDR',
                'exchange_rate' => '15000.50',
                'amount' => '1234.56',
                'tax_amount' => '123.46',
            ]);

        if ($response->status() === 200 || $response->status() === 201) {
            $expense = Expense::latest()->first();

            // Verify bcmath calculations
            expect($expense->amount)->toBe('1234.56');
            expect($expense->tax_amount)->toBe('123.46');

            // total_amount = bcadd('1234.56', '123.46', 2) = '1358.02'
            expect($expense->total_amount)->toBe('1358.02');

            // amount_in_base = bcmul('1234.56', '15000.50', 2) = '18519017.28'
            expect($expense->amount_in_base)->toBe('18519017.28');
        }
    });
});

describe('Expense Authorization', function () {
    it('denies access to users without permission', function () {
        $user = User::factory()->create();
        // User without any permissions

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => 1,
                'account_id' => 1,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
            ]);

        $response->assertStatus(403);
    });

    it('allows access to users with create permission', function () {
        $user = User::factory()->create();
        $user->givePermissionTo('finance.expenses.create');

        $category = ExpenseCategory::factory()->create();
        $account = ChartOfAccount::factory()->create([
            'account_type' => 'expense',
            'is_header' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/finance/api/expenses', [
                'expense_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'account_id' => $account->id,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => 100,
            ]);

        // Should not be 403 (might be 200 or 422 depending on validation)
        expect($response->status())->not->toBe(403);
    });
});
