<?php

namespace Database\Factories;

use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChartOfAccountFactory extends Factory
{
    protected $model = ChartOfAccount::class;

    public function definition(): array
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $type = $this->faker->randomElement($types);

        return [
            'account_code' => $this->faker->unique()->numerify('####-###'),
            'account_name' => $this->faker->words(3, true),
            'account_type' => $type,
            'normal_balance' => in_array($type, ['asset', 'expense']) ? 'debit' : 'credit',
            'parent_id' => null,
            'level' => 1,
            'is_header' => false,
            'is_active' => true,
            'is_system' => false,
            'is_bank_account' => false,
            'description' => $this->faker->optional()->sentence(),
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'created_by' => null,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'expense',
            'normal_balance' => 'debit',
        ]);
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'asset',
            'normal_balance' => 'debit',
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'liability',
            'normal_balance' => 'credit',
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
        ]);
    }

    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_header' => true,
        ]);
    }

    public function bank(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_bank_account' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
