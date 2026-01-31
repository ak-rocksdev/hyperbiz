<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 10000);
        $taxAmount = round($amount * 0.11, 2);
        $exchangeRate = 1;

        return [
            'expense_number' => 'EXP-' . now()->year . '-' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'expense_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'category_id' => ExpenseCategory::factory(),
            'account_id' => ChartOfAccount::factory()->expense(),
            'paid_from_account_id' => null,
            'supplier_id' => null,
            'payee_name' => $this->faker->company(),
            'currency_code' => 'IDR',
            'exchange_rate' => $exchangeRate,
            'amount' => $amount,
            'amount_in_base' => bcmul((string) $amount, (string) $exchangeRate, 2),
            'tax_amount' => $taxAmount,
            'total_amount' => bcadd((string) $amount, (string) $taxAmount, 2),
            'payment_status' => Expense::PAYMENT_UNPAID,
            'amount_paid' => 0,
            'payment_method' => $this->faker->optional()->randomElement(['cash', 'bank_transfer', 'credit_card', 'check']),
            'reference_number' => $this->faker->optional()->bothify('REF-####'),
            'description' => $this->faker->sentence(),
            'notes' => $this->faker->optional()->paragraph(),
            'is_recurring' => false,
            'recurring_frequency' => null,
            'status' => Expense::STATUS_DRAFT,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Expense::STATUS_DRAFT,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Expense::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Expense::STATUS_POSTED,
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'payment_status' => Expense::PAYMENT_PAID,
                'amount_paid' => $attributes['total_amount'],
            ];
        });
    }

    public function partialPaid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAmount = bcmul((string) $attributes['total_amount'], '0.5', 2);
            return [
                'payment_status' => Expense::PAYMENT_PARTIAL,
                'amount_paid' => $paidAmount,
            ];
        });
    }
}
