<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Client;
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

        // return dd($data);
        return Inertia::render('Client/List', [
            'clients' => $data
        ]);
    }
}
