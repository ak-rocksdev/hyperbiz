<?php

namespace Database\Seeders;

use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FiscalYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = Carbon::now()->year;

        // Create current fiscal year
        $fiscalYear = FiscalYear::updateOrCreate(
            [
                'start_date' => Carbon::create($currentYear, 1, 1)->toDateString(),
                'end_date' => Carbon::create($currentYear, 12, 31)->toDateString(),
            ],
            [
                'name' => "FY {$currentYear}",
                'status' => 'open',
                'is_current' => true,
            ]
        );

        // Create monthly periods if they don't exist
        if ($fiscalYear->periods()->count() === 0) {
            $fiscalYear->createPeriods();
        }
    }
}
