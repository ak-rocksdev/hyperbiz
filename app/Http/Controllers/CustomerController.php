<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Address;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class CustomerController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->get('search', null);
        $perPage = $request->get('per_page', 5);

        $customersQuery = Customer::with(['customerType', 'address']);

        if ($search) {
            $customersQuery->where(function ($query) use ($search) {
                $query->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhereHas('customerType', function ($query) use ($search) {
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

        $totalSearchResults = $customersQuery->count();
        $customers = $customersQuery->paginate($perPage);

        $data = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->client_name,
                'email' => $customer->email,
                'customer_type' => $customer->customerType->client_type ?? 'N/A',
                'address' => $customer->address,
                'phone_number' => $customer->client_phone_number,
                'register_at' => Carbon::parse($customer->created_at)->format('d M Y - H:i'),
                'is_customer' => $customer->is_customer,
                'sell_value' => Number::currency($customer->getTotalSell->sum('grand_total'), in: 'IDR', locale: 'id'),
                'purchase_value' => Number::currency($customer->getTotalPurchase->sum('grand_total'), in: 'IDR', locale: 'id'),
            ];
        });

        $totalCustomers = Customer::count();
        $customerCategories = CustomerType::whereHas('customers')->pluck('client_type', 'id');
        $customerCategoriesCount = $customerCategories->count();
        $customerTypes = CustomerType::pluck('client_type', 'id');

        return Inertia::render('Customer/List', [
            'customers' => $data,
            'pagination' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
            ],
            'totalSearchResults' => $totalSearchResults,
            'customerTypes' => $customerTypes,
            'totalCustomers' => $totalCustomers,
            'customerCategoriesCount' => $customerCategoriesCount,
        ]);
    }

    public function detail($id)
    {
        $customer = Customer::find($id);

        $data = [
            'id' => $customer->id,
            'name' => $customer->client_name,
            'email' => $customer->email,
            'customer_type' => $customer->customerType->client_type,
            'is_customer' => $customer->is_customer,
            'address' => $customer->address,
            'phone_number' => $customer->client_phone_number,
            'contact_person' => $customer->contact_person,
            'contact_person_phone_number' => $customer->contact_person_phone_number,
            'register_at' => Carbon::parse($customer->created_at)->format('d M Y - H:i')
        ];

        return Inertia::render('Customer/Detail', [
            'customer' => $data
        ]);
    }

    public function detailApi($id)
    {
        $customer = Customer::with('address')->findOrFail($id);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->client_name,
                'email' => $customer->email,
                'phone_number' => $customer->client_phone_number,
                'customer_type' => $customer->customerType->client_type ?? null,
                'is_customer' => $customer->is_customer,
                'contact_person' => $customer->contact_person ?? null,
                'contact_person_phone_number' => $customer->contact_person_phone_number ?? null,
                'address' => $customer->address ? [
                    'address' => $customer->address->address ?? null,
                    'city_name' => $customer->address->city_name ?? null,
                    'state_name' => $customer->address->state_name ?? null,
                    'country_name' => $customer->address->country_name ?? null,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mst_customer_type_id' => 'required|exists:mst_client_type,id',
            'customer_phone_number' => 'required|string|max:20',
            'contact_person' => 'required|string|max:255',
            'contact_person_phone_number' => 'required|string|max:20',
            'is_customer' => 'required|boolean',
        ], [
            'customer_name.required' => 'The customer name is required.',
            'email.required' => 'A valid email address is required.',
            'mst_customer_type_id.required' => 'Please select a customer type.',
            'customer_phone_number.required' => 'The customer phone number is required.',
            'contact_person.required' => 'The contact person name is required.',
            'contact_person_phone_number.required' => 'The contact person phone number is required.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::create([
                'client_name' => $validatedData['customer_name'],
                'mst_client_type_id' => $validatedData['mst_customer_type_id'],
                'client_phone_number' => $validatedData['customer_phone_number'],
                'email' => $validatedData['email'],
                'contact_person' => $validatedData['contact_person'],
                'contact_person_phone_number' => $validatedData['contact_person_phone_number'],
                'is_customer' => $validatedData['is_customer'],
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);

        $data = [
            'id' => $customer->id,
            'customer_name' => $customer->client_name,
            'email' => $customer->email,
            'mst_customer_type_id' => $customer->mst_client_type_id,
            'is_customer' => $customer->is_customer,
            'mst_address_id' => $customer->mst_address_id,
            'address' => [
                'address' => $customer->address->address ?? '',
                'city_name' => $customer->address->city_name ?? '',
                'state_name' => $customer->address->state_name ?? '',
                'country_name' => $customer->address->country_name ?? '',
            ],
            'customer_phone_number' => $customer->client_phone_number,
            'contact_person' => $customer->contact_person,
            'contact_person_phone_number' => $customer->contact_person_phone_number
        ];

        $customerTypes = CustomerType::pluck('client_type', 'id');

        return Inertia::render('Customer/Edit', [
            'customer' => $data,
            'customerTypes' => $customerTypes,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validatedCustomer = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email',
            'customer_phone_number' => 'required|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_phone_number' => 'nullable|string|max:20',
            'mst_customer_type_id' => 'required|exists:mst_client_type,id',
            'is_customer' => 'required|in:1,0',
        ]);

        $validatedAddress = $request->validate([
            'address.address' => 'nullable|max:255',
            'address.city_name' => 'nullable|max:255',
            'address.state_name' => 'nullable|max:255',
            'address.country_name' => 'nullable|max:255',
        ]);

        $customer = Customer::findOrFail($id);
        $address = Address::find($customer->mst_address_id);

        // Map validated data to database columns
        $customerData = [
            'client_name' => $validatedCustomer['customer_name'],
            'email' => $validatedCustomer['email'],
            'client_phone_number' => $validatedCustomer['customer_phone_number'],
            'contact_person' => $validatedCustomer['contact_person'],
            'contact_person_phone_number' => $validatedCustomer['contact_person_phone_number'],
            'mst_client_type_id' => $validatedCustomer['mst_customer_type_id'],
            'is_customer' => $validatedCustomer['is_customer'],
        ];

        $customerDirty = $customer->fill($customerData)->isDirty();
        if ($customerDirty) {
            $customer->save();
        }

        $addressDirty = false;
        if ($address) {
            $addressDirty = $address->fill($validatedAddress['address'])->isDirty();
            if ($addressDirty) {
                $address->save();
            }
        } elseif (!empty($validatedAddress['address']['address'])) {
            $newAddress = Address::create($validatedAddress['address']);
            $customer->mst_address_id = $newAddress->id;
            $customer->save();
            $addressDirty = true;
        }

        if (!$customerDirty && !$addressDirty) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected.',
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
        ], 200);
    }
}
