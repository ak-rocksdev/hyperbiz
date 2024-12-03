<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Address;
use App\Models\ClientType;

class ClientFactory extends Factory
{
    public function definition()
    {
        return [
            'client_name' => $this->faker->company(),
            'mst_address_id' => Address::factory(), // Generate related Address
            'client_phone_number' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'contact_person' => $this->faker->name(),
            'contact_person_phone_number' => $this->faker->phoneNumber(),
            'mst_client_type_id' => ClientType::factory(), // Generate related ClientType
            'created_by' => 'Seeder',
            'updated_by' => null,
        ];
    }
}
