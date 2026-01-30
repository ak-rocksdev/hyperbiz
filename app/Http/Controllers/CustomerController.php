<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Address;
use App\Models\SalesOrder;
use App\Models\Payment;
use App\Models\SalesReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class CustomerController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->get('search', null);
        $perPage = $request->get('per_page', 10);
        $statusFilter = $request->get('status', 'all'); // all, active, inactive

        $customersQuery = Customer::with(['customerType', 'address'])
            ->withCount([
                'salesOrders as orders_count' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ])
            ->withSum([
                'salesOrders as total_sales' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ], 'grand_total')
            ->withSum([
                'salesOrders as outstanding_amount' => function ($query) {
                    $query->where('status', '!=', 'cancelled')
                          ->whereIn('payment_status', ['unpaid', 'partial']);
                }
            ], 'grand_total')
            ->withSum([
                'salesOrders as amount_paid' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ], 'amount_paid');

        // Status filter
        if ($statusFilter === 'active') {
            $customersQuery->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $customersQuery->where('is_active', false);
        }

        // Search filter
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

        $customers = $customersQuery->orderByDesc('created_at')->paginate($perPage);

        // Get last order date for each customer
        $customerIds = $customers->pluck('id');
        $lastOrders = SalesOrder::whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('customer_id, MAX(order_date) as last_order_date')
            ->groupBy('customer_id')
            ->pluck('last_order_date', 'customer_id');

        $data = $customers->map(function ($customer) use ($lastOrders) {
            $outstanding = ($customer->total_sales ?? 0) - ($customer->amount_paid ?? 0);
            return [
                'id' => $customer->id,
                'name' => $customer->client_name,
                'email' => $customer->email,
                'customer_type' => $customer->customerType->client_type ?? 'N/A',
                'customer_type_id' => $customer->mst_client_type_id,
                'address' => $customer->address,
                'phone_number' => $customer->client_phone_number,
                'is_customer' => $customer->is_customer,
                'is_active' => $customer->is_active,
                'orders_count' => $customer->orders_count ?? 0,
                'total_sales' => $customer->total_sales ?? 0,
                'outstanding' => max(0, $outstanding),
                'last_order_date' => $lastOrders[$customer->id] ?? null,
                'created_at' => Carbon::parse($customer->created_at)->format('d M Y'),
            ];
        });

        // Statistics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('is_active', true)->count();
        $inactiveCustomers = Customer::where('is_active', false)->count();

        // Financial stats (from sales orders)
        $totalRevenue = SalesOrder::where('status', '!=', 'cancelled')->sum('grand_total');
        $totalPaid = SalesOrder::where('status', '!=', 'cancelled')->sum('amount_paid');
        $totalOutstanding = $totalRevenue - $totalPaid;

        // Customer types for filter dropdown
        $customerTypes = CustomerType::select('id', 'client_type')
            ->orderBy('client_type')
            ->get()
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->client_type]);

        return Inertia::render('Customer/List', [
            'customers' => $data,
            'customerTypes' => $customerTypes,
            'pagination' => [
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
            'stats' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'inactive_customers' => $inactiveCustomers,
                'total_revenue' => $totalRevenue,
                'total_outstanding' => max(0, $totalOutstanding),
            ],
        ]);
    }

    public function detail($id)
    {
        $customer = Customer::with(['customerType', 'address'])
            ->withCount([
                'salesOrders as orders_count' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ])
            ->withSum([
                'salesOrders as total_sales' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ], 'grand_total')
            ->withSum([
                'salesOrders as amount_paid' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ], 'amount_paid')
            ->findOrFail($id);

        $outstanding = ($customer->total_sales ?? 0) - ($customer->amount_paid ?? 0);
        $avgOrderValue = $customer->orders_count > 0
            ? ($customer->total_sales / $customer->orders_count)
            : 0;

        // Get last order date
        $lastOrder = SalesOrder::where('customer_id', $id)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('order_date')
            ->first();

        // Recent sales orders (last 5)
        $recentOrders = SalesOrder::where('customer_id', $id)
            ->orderByDesc('order_date')
            ->limit(5)
            ->get()
            ->map(fn($so) => [
                'id' => $so->id,
                'so_number' => $so->so_number,
                'order_date' => Carbon::parse($so->order_date)->format('d M Y'),
                'grand_total' => $so->grand_total,
                'status' => $so->status,
                'payment_status' => $so->payment_status,
                'amount_paid' => $so->amount_paid,
            ]);

        // Recent payments (last 5) - via sales orders
        $recentPayments = Payment::where('payment_type', 'sales')
            ->where('reference_type', 'sales_order')
            ->whereIn('reference_id', SalesOrder::where('customer_id', $id)->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('payment_date')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'payment_date' => Carbon::parse($p->payment_date)->format('d M Y'),
                'amount' => $p->amount,
                'payment_method' => $p->payment_method,
                'status' => $p->status,
            ]);

        // Sales returns (if any)
        $salesReturns = SalesReturn::where('customer_id', $id)
            ->orderByDesc('return_date')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'return_number' => $r->return_number,
                'return_date' => Carbon::parse($r->return_date)->format('d M Y'),
                'subtotal' => $r->subtotal,
                'status' => $r->status,
                'reason' => $r->reason,
            ]);

        // Get created_by and updated_by user names
        $createdBy = $customer->created_by
            ? \App\Models\User::find($customer->created_by)?->name
            : null;
        $updatedBy = $customer->updated_by
            ? \App\Models\User::find($customer->updated_by)?->name
            : null;

        $data = [
            'id' => $customer->id,
            'name' => $customer->client_name,
            'email' => $customer->email,
            'phone_number' => $customer->client_phone_number,
            'customer_type' => $customer->customerType->client_type ?? 'N/A',
            'customer_type_id' => $customer->mst_client_type_id,
            'is_customer' => $customer->is_customer,
            'is_active' => $customer->is_active,
            'contact_person' => $customer->contact_person,
            'contact_person_phone_number' => $customer->contact_person_phone_number,
            'address' => $customer->address ? [
                'address' => $customer->address->address,
                'city_name' => $customer->address->city_name,
                'state_name' => $customer->address->state_name,
                'country_name' => $customer->address->country_name,
            ] : null,
            'created_at' => Carbon::parse($customer->created_at)->format('d M Y H:i'),
            'updated_at' => $customer->updated_at
                ? Carbon::parse($customer->updated_at)->format('d M Y H:i')
                : null,
            'created_by' => $createdBy,
            'updated_by' => $updatedBy,
        ];

        $stats = [
            'orders_count' => $customer->orders_count ?? 0,
            'total_sales' => $customer->total_sales ?? 0,
            'outstanding' => max(0, $outstanding),
            'avg_order_value' => $avgOrderValue,
            'last_order_date' => $lastOrder
                ? Carbon::parse($lastOrder->order_date)->format('d M Y')
                : null,
            'days_since_last_order' => $lastOrder
                ? (int) Carbon::parse($lastOrder->order_date)->diffInDays(now())
                : null,
        ];

        return Inertia::render('Customer/Detail', [
            'customer' => $data,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'recentPayments' => $recentPayments,
            'salesReturns' => $salesReturns,
        ]);
    }

    public function detailApi($id)
    {
        $customer = Customer::with(['address', 'customerType'])->findOrFail($id);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->client_name,
                'email' => $customer->email,
                'phone_number' => $customer->client_phone_number,
                'customer_type' => $customer->customerType->client_type ?? null,
                'is_customer' => $customer->is_customer,
                'is_active' => $customer->is_active,
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

    public function edit($id)
    {
        $customer = Customer::with(['customerType', 'address'])
            ->withCount([
                'salesOrders as orders_count' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ])
            ->withSum([
                'salesOrders as total_sales' => function ($query) {
                    $query->where('status', '!=', 'cancelled');
                }
            ], 'grand_total')
            ->findOrFail($id);

        $data = [
            'id' => $customer->id,
            'customer_name' => $customer->client_name,
            'email' => $customer->email,
            'mst_customer_type_id' => $customer->mst_client_type_id,
            'is_customer' => $customer->is_customer,
            'is_active' => $customer->is_active,
            'mst_address_id' => $customer->mst_address_id,
            'address' => [
                'address' => $customer->address->address ?? '',
                'city_name' => $customer->address->city_name ?? '',
                'state_name' => $customer->address->state_name ?? '',
                'country_name' => $customer->address->country_name ?? '',
            ],
            'customer_phone_number' => $customer->client_phone_number,
            'contact_person' => $customer->contact_person,
            'contact_person_phone_number' => $customer->contact_person_phone_number,
            'orders_count' => $customer->orders_count ?? 0,
            'total_sales' => $customer->total_sales ?? 0,
        ];

        $customerTypes = CustomerType::select('id', 'client_type')
            ->orderBy('client_type')
            ->get()
            ->map(fn($t) => ['value' => $t->id, 'label' => $t->client_type]);

        return Inertia::render('Customer/Edit', [
            'customer' => $data,
            'customerTypes' => $customerTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mst_customer_type_id' => 'required|exists:mst_client_type,id',
            'customer_phone_number' => 'required|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_phone_number' => 'nullable|string|max:20',
            'is_customer' => 'required|boolean',
        ], [
            'customer_name.required' => 'The customer name is required.',
            'email.required' => 'A valid email address is required.',
            'mst_customer_type_id.required' => 'Please select a customer type.',
            'customer_phone_number.required' => 'The customer phone number is required.',
        ]);

        DB::beginTransaction();
        try {
            $customer = Customer::create([
                'client_name' => $validatedData['customer_name'],
                'mst_client_type_id' => $validatedData['mst_customer_type_id'],
                'client_phone_number' => $validatedData['customer_phone_number'],
                'email' => $validatedData['email'],
                'contact_person' => $validatedData['contact_person'] ?? null,
                'contact_person_phone_number' => $validatedData['contact_person_phone_number'] ?? null,
                'is_customer' => $validatedData['is_customer'],
                'is_active' => true,
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

    /**
     * Toggle customer active status (activate/deactivate)
     */
    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);

        DB::beginTransaction();
        try {
            $customer->is_active = !$customer->is_active;
            $customer->save();

            DB::commit();

            $action = $customer->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Customer {$action} successfully.",
                'is_active' => $customer->is_active,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
