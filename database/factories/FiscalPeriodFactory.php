<?php

namespace Database\Factories;

use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class FiscalPeriodFactory extends Factory
{
    protected $model = FiscalPeriod::class;

    public function definition(): array
    {
        $month = $this->faker->numberBetween(1, 12);
        $year = now()->year;
        $startDate = now()->setDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'name' => $startDate->format('F Y'),
            'period_number' => $month,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => 'open',
            'is_adjusting_period' => false,
            'closed_by' => null,
            'closed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    public function adjusting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'adjusting',
            'is_adjusting_period' => true,
        ]);
    }
}
