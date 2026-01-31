<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
    {
        $code = strtoupper($this->faker->unique()->lexify('EXP-???'));

        return [
            'code' => $code,
            'name' => $this->faker->words(3, true),
            'parent_id' => null,
            'default_account_id' => null,
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'created_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
