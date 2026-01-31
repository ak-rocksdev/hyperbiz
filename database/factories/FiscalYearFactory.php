<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    public function definition(): array
    {
        $year = $this->faker->unique()->numberBetween(2020, 2030);
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        return [
            'name' => "Fiscal Year {$year}",
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
            'is_current' => false,
            'created_by' => null,
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_current' => true,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }
}
