<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanySeeder extends Seeder
{
    public function run()
    {
        DB::table('mst_company')->insert([
            'name' => 'Example Company',
            'address' => '123 Example Street',
            'phone' => '123-456-7890',
            'email' => 'info@example.com',
            'website' => 'https://www.example.com',
            'logo' => 'logos/example_logo.png',
            'created_by' => 1, // Assuming user with ID 1 exists
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
