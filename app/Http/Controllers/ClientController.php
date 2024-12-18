<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Client;
use App\Models\ClientType;
use Carbon\Carbon;

class ClientController extends Controller
{
    public function list()
    {
        $clients = Client::all();

        $data = $clients->map(function($client) {
            return [
                'id' => $client->id,
                'name' => $client->client_name,
                'email' => $client->email,
                'client_type' => $client->clientType->client_type,
                'address' => $client->address,
                'phone_number' => $client->client_phone_number,
                'register_at' => Carbon::parse($client->created_at)->format('d M Y - H:i')
            ];
        });

        $clientTypes = ClientType::pluck('client_type', 'id');

        // return dd($data);
        return Inertia::render('Client/List', [
            'clients' => $data,
            'clientTypes' => $clientTypes
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
        ]);

        $validatedData['created_by'] = auth()->id();

        $client = Client::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully.',
        ], 201);
    }
}
