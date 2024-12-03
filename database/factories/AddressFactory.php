<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    public function definition()
    {
        return [
            'address' => $this->faker->streetAddress(),
            'city_id' => $this->faker->randomNumber(5, true),
            'city_name' => $this->faker->city(),
            'state_name' => $this->faker->state(),
            'state_id' => $this->faker->randomNumber(5, true),
            'country_id' => $this->faker->randomNumber(5, true),
            'country_name' => $this->faker->country(),
            'created_by' => 'Seeder',
            'updated_by' => null,
        ];
    }
}
