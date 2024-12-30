<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Address;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->get('search', null);
        $perPage = $request->get('per_page', 5); // Default to 5 items per page

        // Query clients with optional search
        $clientsQuery = Client::with(['clientType', 'address']);
        if ($search) {
            $clientsQuery->where(function ($query) use ($search) {
                $query->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('clientType', function ($query) use ($search) {
                        $query->where('client_type', 'like', '%' . $search . '%');
                    })
                    ->orWhere('client_phone_number', 'like', '%' . $search . '%')
                    ->orWhereHas('address', function ($query) use ($search) {
                        $query->where('city_name', 'like', '%' . $search . '%')
                                ->orWhere('state_name', 'like', '%' . $search . '%')
                                ->orWhere('country_name', 'like', '%' . $search . '%');
                    });
                });
        }

        $totalSearchResults = $clientsQuery->count();

        $clients = $clientsQuery->paginate($perPage);

        // Map paginated data for the frontend
        $data = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->client_name,
                'email' => $client->email,
                'client_type' => $client->clientType->client_type ?? 'N/A',
                'address' => $client->address,
                'phone_number' => $client->client_phone_number,
                'register_at' => Carbon::parse($client->created_at)->format('d M Y - H:i'),
            ];
        });

        // count all clients
        $totalClients = Client::count();

        // get categories where has client 1 or more
        $clientCategories = ClientType::whereHas('clients')->pluck('client_type', 'id');
        $clientCategoriesCount = $clientCategories->count();

        $clientTypes = ClientType::pluck('client_type', 'id');

        return Inertia::render('Client/List', [
            'clients' => $data,
            'pagination' => [
                'total' => $clients->total(),
                'per_page' => $clients->perPage(),
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
            ],
            'totalSearchResults' => $totalSearchResults,
            'clientTypes' => $clientTypes,
            'totalClients' => $totalClients,
            'clientCategoriesCount' => $clientCategoriesCount,
        ]);
    }

    public function detail($id)
    {
        $client = Client::find($id);

        $data = [
            'id' => $client->id,
            'name' => $client->client_name,
            'email' => $client->email,
            'client_type' => $client->clientType->client_type,
            'address' => $client->address,
            'phone_number' => $client->client_phone_number,
            'contact_person' => $client->contact_person,
            'contact_person_phone_number' => $client->contact_person_phone_number,
            'register_at' => Carbon::parse($client->created_at)->format('d M Y - H:i')
        ];

        return Inertia::render('Client/Detail', [
            'client' => $data
        ]);
    }

    public function detailApi($id)
    {
        $client = Client::with('address')->findOrFail($id);

        return response()->json([
            'client' => [
                'id' => $client->id,
                'name' => $client->client_name,
                'email' => $client->email,
                'phone_number' => $client->client_phone_number,
                'client_type' => $client->clientType->client_type ?? null,
                'contact_person' => $client->contact_person ?? null,
                'contact_person_phone_number' => $client->contact_person_phone_number ?? null,
                'address' => $client->address ? [
                    'address' => $client->address->address ?? null,
                    'city_name' => $client->address->city_name ?? null,
                    'state_name' => $client->address->state_name ?? null,
                    'country_name' => $client->address->country_name ?? null,
                ] : null,
            ],
        ]);
    }

    // store client
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'client_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mst_client_type_id' => 'required|exists:mst_client_type,id',
            'client_phone_number' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'contact_person_phone_number' => 'required|string|max:20',
        ], [
            'client_name.required' => 'The client name is required.',
            'email.required' => 'A valid email address is required.',
            'mst_client_type_id.required' => 'Please select a client type.',
            'client_phone_number.required' => 'The client phone number is required.',
            'contact_person.required' => 'The contact person name is required.',
            'contact_person_phone_number.required' => 'The contact person phone number is required.',
        ]);

        DB::beginTransaction();
        try {
            $validatedData['created_by'] = auth()->id();
            $client = Client::create($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Client created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create client.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // show edit page
    public function edit($id)
    {
        $client = Client::findOrFail($id); // Use `findOrFail` to handle non-existent clients gracefully

        $data = [
            'id' => $client->id,
            'client_name' => $client->client_name,
            'email' => $client->email,
            'mst_client_type_id' => $client->mst_client_type_id,
            'mst_address_id' => $client->mst_address_id,
            'address' => [
                'address' => $client->address->address ?? '',
                'city_name' => $client->address->city_name ?? '',
                'state_name' => $client->address->state_name ?? '',
                'country_name' => $client->address->country_name ?? '',
            ],
            'client_phone_number' => $client->client_phone_number,
            'contact_person' => $client->contact_person,
            'contact_person_phone_number' => $client->contact_person_phone_number
        ];

        $clientTypes = ClientType::pluck('client_type', 'id');

        return Inertia::render('Client/Edit', [
            'client' => $data,
            'clientTypes' => $clientTypes,
        ]);
    }

    public function update(Request $request, $id)
    {
        // Validate client-related fields
        $validatedClient = $request->validate([
            'client_name' => 'required|string|max:255',
            'email' => 'required|email',
            'client_phone_number' => 'required|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_phone_number' => 'nullable|string|max:20',
            'mst_client_type_id' => 'required|exists:mst_client_type,id',
        ]);

        // Validate address-related fields
        $validatedAddress = $request->validate([
            'address.address' => 'required|max:255',
            'address.city_name' => 'required|max:255',
            'address.state_name' => 'required|max:255',
            'address.country_name' => 'required|max:255',
        ]);

        // Fetch client model
        $client = Client::findOrFail($id);

        // Check if the client has an associated address
        $address = Address::find($client->mst_address_id);

        // Update client if changes exist
        $clientDirty = $client->fill($validatedClient)->isDirty();
        if ($clientDirty) {
            $client->save();
        }

        // Handle address logic
        $addressDirty = false;
        if ($address) {
            // Update existing address
            $addressDirty = $address->fill($validatedAddress['address'])->isDirty();
            if ($addressDirty) {
                $address->save();
            }
        } elseif (!empty($validatedAddress['address']['address'])) {
            // Create new address if no address exists and address data is provided
            $newAddress = Address::create($validatedAddress['address']);
            $client->mst_address_id = $newAddress->id;
            $client->save(); // Update client with the new address ID
            $addressDirty = true;
        }

        // Prepare response
        if (!$clientDirty && !$addressDirty) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully.',
        ], 200);
    }

}
