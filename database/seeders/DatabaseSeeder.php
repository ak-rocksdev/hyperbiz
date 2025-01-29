<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Address;
use App\Models\ClientType;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(50)->create();
        // Predefined client types
        $clientTypes = [
            'Importir',
            'Distributor',
            'Retailer',
            'Wholesaler',
            'Agent',
            'Supplier',
            'Manufacturer',
        ];

        // Create unique client types
        $clientTypeRecords = ClientType::factory()
            ->state(new \Illuminate\Database\Eloquent\Factories\Sequence(
                ...array_map(fn($type) => ['client_type' => $type], $clientTypes)
            ))
            ->count(count($clientTypes))
            ->create();

        // Create clients with unique addresses
        $clients = collect(range(1, 30))->map(function ($index) use ($clientTypeRecords) {
            // Create a unique address for each client
            $address = Address::factory()->create();

            // Assign a client type randomly, ensuring diversity
            $clientTypeId = $clientTypeRecords->random()->id;

            // Create the client with the unique address
            return Client::factory()->create([
                'mst_address_id' => $address->id,
                'mst_client_type_id' => $clientTypeId,
            ]);
        });

        // Log the seeded data
        $this->command->info("Seeded {$clients->count()} clients, each with unique addresses and diversified client types.");

        
        // User::factory()->withPersonalTeam()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
