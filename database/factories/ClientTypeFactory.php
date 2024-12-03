<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientTypeFactory extends Factory
{
    public function definition()
    {
        $clientTypes = ['Importir', 'Distributor', 'Retailer', 'Wholesaler', 'Agent', 'Supplier', 'Manufacturer'];

        return [
            'client_type' => $this->faker->randomElement($clientTypes),
            'created_by' => 'Seeder',
            'updated_by' => null,
        ];
    }
}
