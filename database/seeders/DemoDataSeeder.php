<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReceiving;
use App\Models\PurchaseReceivingItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesShipment;
use App\Models\SalesShipmentItem;
use App\Models\Uom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    // Seasonality multipliers for each month
    protected array $seasonalityMultipliers = [
        1 => 0.7,   // January - New Year slowdown
        2 => 0.8,   // February - Post-holiday
        3 => 1.0,   // March - Normal
        4 => 1.1,   // April - Ramadan/Easter
        5 => 1.2,   // May - Eid peak
        6 => 0.9,   // June - Post-Eid dip
        7 => 1.0,   // July - Mid-year
        8 => 1.3,   // August - Back-to-school, Independence Day
        9 => 1.1,   // September - Steady
        10 => 1.0,  // October - Normal
        11 => 1.4,  // November - Singles Day, Pre-holiday
        12 => 1.6,  // December - Christmas/Year-end peak
    ];

    protected array $brands = [];
    protected array $categories = [];
    protected array $suppliers = [];
    protected array $customers = [];
    protected array $products = [];
    protected array $uoms = [];
    protected array $clientTypes = [];

    protected Carbon $startDate;
    protected Carbon $endDate;

    public function run(): void
    {
        $this->command->info('Starting Demo Data Seeder...');
        $this->command->newLine();

        // Login as admin user for audit trail fields
        $adminUser = User::find(1);
        if ($adminUser) {
            Auth::login($adminUser);
            $this->command->info('Logged in as: ' . $adminUser->name);
        } else {
            $this->command->warn('No admin user found with ID 1. Will use DB::table for inserts.');
        }

        // Set date range: Feb 1, 2025 to today
        $this->startDate = Carbon::create(2025, 2, 1);
        $this->endDate = Carbon::now();

        DB::beginTransaction();

        try {
            // Load existing UOMs
            $this->loadUoms();

            // Load or create client types
            $this->loadClientTypes();

            // Step 1: Seed master data
            $this->seedBrands();
            $this->seedCategories();
            $this->seedSuppliers();
            $this->seedCustomers();
            $this->seedProducts();

            // Step 2: Seed transactions chronologically
            $this->seedTransactionsChronologically();

            DB::commit();

            $this->command->newLine();
            $this->command->info('Demo data seeding completed successfully!');
            $this->printSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error during seeding: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }

    protected function loadUoms(): void
    {
        $this->uoms = Uom::where('is_active', true)->pluck('id', 'code')->toArray();

        if (empty($this->uoms)) {
            $this->command->warn('No UOMs found. Running UomSeeder first...');
            $this->call(UomSeeder::class);
            $this->uoms = Uom::where('is_active', true)->pluck('id', 'code')->toArray();
        }

        $this->command->info('Loaded ' . count($this->uoms) . ' UOMs');
    }

    protected function loadClientTypes(): void
    {
        $this->clientTypes = CustomerType::pluck('id', 'client_type')->toArray();

        if (empty($this->clientTypes)) {
            // Create default client types
            $types = [
                ['client_type' => 'Supplier', 'can_purchase' => true, 'can_sell' => false, 'description' => 'Product suppliers'],
                ['client_type' => 'Manufacturer', 'can_purchase' => true, 'can_sell' => false, 'description' => 'Product manufacturers'],
                ['client_type' => 'Distributor', 'can_purchase' => true, 'can_sell' => true, 'description' => 'Distributors'],
                ['client_type' => 'Retailer', 'can_purchase' => false, 'can_sell' => true, 'description' => 'Retail customers'],
                ['client_type' => 'Wholesaler', 'can_purchase' => false, 'can_sell' => true, 'description' => 'Wholesale customers'],
                ['client_type' => 'End Customer', 'can_purchase' => false, 'can_sell' => true, 'description' => 'End consumers'],
            ];

            foreach ($types as $type) {
                $ct = CustomerType::create($type);
                $this->clientTypes[$ct->client_type] = $ct->id;
            }
        }

        $this->command->info('Loaded ' . count($this->clientTypes) . ' client types');
    }

    protected function seedBrands(): void
    {
        $this->command->info('Seeding brands...');

        $brandNames = [
            // Electronics
            'Samsung', 'Apple', 'Xiaomi', 'Sony', 'JBL', 'Logitech', 'Asus',
            // Fashion
            'Nike', 'Adidas', 'Uniqlo', 'H&M', 'Zara',
            // Home & Living
            'IKEA', 'Informa', 'ACE',
            // Food & Beverages
            'Nestle', 'Indofood', 'Unilever',
            // Health & Beauty
            'L\'Oreal', 'Maybelline', 'Wardah',
        ];

        foreach ($brandNames as $name) {
            $brand = Brand::create([
                'name' => $name,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
            $this->brands[$name] = $brand->id;
        }

        $this->command->info('Created ' . count($this->brands) . ' brands');
    }

    protected function seedCategories(): void
    {
        $this->command->info('Seeding product categories...');

        $categoryStructure = [
            'Electronics' => ['Smartphones', 'Laptops & Computers', 'Audio & Headphones', 'Accessories', 'Gaming'],
            'Fashion' => ['Men\'s Clothing', 'Women\'s Clothing', 'Footwear', 'Bags & Accessories'],
            'Home & Living' => ['Furniture', 'Kitchen & Dining', 'Decor', 'Bedding', 'Storage'],
            'Food & Beverages' => ['Snacks', 'Beverages', 'Pantry Staples', 'Dairy'],
            'Health & Beauty' => ['Skincare', 'Haircare', 'Personal Care', 'Makeup'],
        ];

        foreach ($categoryStructure as $parentName => $children) {
            $parent = ProductCategory::create([
                'name' => $parentName,
                'parent_id' => null,
                'created_by' => 1,
                'updated_by' => 1,
            ]);
            $this->categories[$parentName] = $parent->id;

            foreach ($children as $childName) {
                $child = ProductCategory::create([
                    'name' => $childName,
                    'parent_id' => $parent->id,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
                $this->categories[$childName] = $child->id;
            }
        }

        $this->command->info('Created ' . count($this->categories) . ' categories');
    }

    protected function seedSuppliers(): void
    {
        $this->command->info('Seeding suppliers...');

        $supplierData = [
            ['name' => 'PT Samsung Electronics Indonesia', 'city' => 'Jakarta', 'brands' => ['Samsung']],
            ['name' => 'Apple Authorized Distributor', 'city' => 'Jakarta', 'brands' => ['Apple']],
            ['name' => 'PT Xiaomi Technology Indonesia', 'city' => 'Jakarta', 'brands' => ['Xiaomi']],
            ['name' => 'Sony Indonesia', 'city' => 'Jakarta', 'brands' => ['Sony', 'JBL']],
            ['name' => 'PT Asics Indonesia', 'city' => 'Tangerang', 'brands' => ['Nike', 'Adidas']],
            ['name' => 'Fast Retailing Indonesia', 'city' => 'Jakarta', 'brands' => ['Uniqlo']],
            ['name' => 'H&M Hennes & Mauritz', 'city' => 'Jakarta', 'brands' => ['H&M', 'Zara']],
            ['name' => 'IKEA Indonesia', 'city' => 'Tangerang', 'brands' => ['IKEA']],
            ['name' => 'PT Ace Hardware Indonesia', 'city' => 'Jakarta', 'brands' => ['ACE', 'Informa']],
            ['name' => 'PT Nestle Indonesia', 'city' => 'Karawang', 'brands' => ['Nestle']],
            ['name' => 'PT Indofood Sukses Makmur', 'city' => 'Jakarta', 'brands' => ['Indofood']],
            ['name' => 'PT Unilever Indonesia', 'city' => 'Jakarta', 'brands' => ['Unilever']],
            ['name' => 'PT L\'Oreal Indonesia', 'city' => 'Jakarta', 'brands' => ['L\'Oreal', 'Maybelline']],
            ['name' => 'PT Paragon Technology', 'city' => 'Tangerang', 'brands' => ['Wardah']],
            ['name' => 'Logitech Distribution', 'city' => 'Jakarta', 'brands' => ['Logitech', 'Asus']],
        ];

        $supplierTypes = ['Supplier', 'Manufacturer', 'Distributor'];

        foreach ($supplierData as $data) {
            $address = Address::create([
                'address' => fake()->streetAddress(),
                'city_name' => $data['city'],
                'state_name' => $this->getStateForCity($data['city']),
                'country_name' => 'Indonesia',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $typeKey = $supplierTypes[array_rand($supplierTypes)];
            $typeId = $this->clientTypes[$typeKey] ?? array_values($this->clientTypes)[0];

            $supplier = Customer::create([
                'client_name' => $data['name'],
                'mst_address_id' => $address->id,
                'client_phone_number' => $this->generatePhoneNumber(),
                'email' => Str::slug($data['name']) . '@example.com',
                'contact_person' => fake()->name(),
                'contact_person_phone_number' => $this->generatePhoneNumber(),
                'mst_client_type_id' => $typeId,
                'is_customer' => false,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $this->suppliers[] = [
                'id' => $supplier->id,
                'name' => $data['name'],
                'brands' => $data['brands'],
            ];
        }

        $this->command->info('Created ' . count($this->suppliers) . ' suppliers');
    }

    protected function seedCustomers(): void
    {
        $this->command->info('Seeding customers...');

        // Indonesian cities with distribution weights
        $cities = [
            ['name' => 'Jakarta', 'weight' => 35],
            ['name' => 'Surabaya', 'weight' => 15],
            ['name' => 'Bandung', 'weight' => 12],
            ['name' => 'Medan', 'weight' => 10],
            ['name' => 'Semarang', 'weight' => 6],
            ['name' => 'Makassar', 'weight' => 5],
            ['name' => 'Palembang', 'weight' => 4],
            ['name' => 'Tangerang', 'weight' => 4],
            ['name' => 'Denpasar', 'weight' => 3],
            ['name' => 'Yogyakarta', 'weight' => 3],
            ['name' => 'Batam', 'weight' => 2],
            ['name' => 'Balikpapan', 'weight' => 1],
        ];

        $customerTypes = ['Retailer', 'Wholesaler', 'End Customer', 'Distributor'];
        $customerCount = 90;

        for ($i = 0; $i < $customerCount; $i++) {
            $city = $this->weightedRandom($cities);

            $address = Address::create([
                'address' => fake()->streetAddress(),
                'city_name' => $city['name'],
                'state_name' => $this->getStateForCity($city['name']),
                'country_name' => 'Indonesia',
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $typeKey = $customerTypes[array_rand($customerTypes)];
            $typeId = $this->clientTypes[$typeKey] ?? array_values($this->clientTypes)[0];

            $isCompany = rand(0, 100) < 40; // 40% companies, 60% individuals
            $name = $isCompany
                ? $this->generateCompanyName()
                : fake()->name();

            $customer = Customer::create([
                'client_name' => $name,
                'mst_address_id' => $address->id,
                'client_phone_number' => $this->generatePhoneNumber(),
                'email' => Str::slug($name) . $i . '@example.com',
                'contact_person' => $isCompany ? fake()->name() : null,
                'contact_person_phone_number' => $isCompany ? $this->generatePhoneNumber() : null,
                'mst_client_type_id' => $typeId,
                'is_customer' => true,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $this->customers[] = [
                'id' => $customer->id,
                'name' => $name,
                'city' => $city['name'],
            ];
        }

        $this->command->info('Created ' . count($this->customers) . ' customers');
    }

    protected function seedProducts(): void
    {
        $this->command->info('Seeding products...');

        $productDefinitions = $this->getProductDefinitions();

        foreach ($productDefinitions as $product) {
            $brandId = $this->brands[$product['brand']] ?? null;
            $categoryId = $this->categories[$product['category']] ?? null;
            $supplierId = $this->getSupplierIdForBrand($product['brand']);

            // Determine UOM based on category
            $uomCode = $this->getUomForCategory($product['category']);
            $uomId = $this->uoms[$uomCode] ?? $this->uoms['PCS'] ?? 1;

            $costPrice = $product['cost'];
            $sellingPrice = $costPrice * (1 + rand(20, 50) / 100); // 20-50% margin

            $dbProduct = Product::create([
                'name' => $product['name'],
                'description' => $product['description'] ?? null,
                'sku' => $this->generateSku($product['category'], $product['brand']),
                'barcode' => $this->generateBarcode(),
                'price' => round($sellingPrice, -3), // Round to nearest 1000
                'cost_price' => $costPrice,
                'currency' => 'IDR',
                'stock_quantity' => 0, // Will be updated by inventory
                'min_stock_level' => rand(10, 30),
                'uom_id' => $uomId,
                'mst_product_category_id' => $categoryId,
                'mst_brand_id' => $brandId,
                'mst_client_id' => $supplierId,
                'weight' => rand(100, 5000) / 1000, // 0.1 - 5 kg
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            // Create initial inventory stock record
            InventoryStock::create([
                'product_id' => $dbProduct->id,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
                'reorder_level' => rand(10, 30),
                'last_cost' => $costPrice,
                'average_cost' => $costPrice,
            ]);

            $this->products[] = [
                'id' => $dbProduct->id,
                'name' => $product['name'],
                'brand' => $product['brand'],
                'category' => $product['category'],
                'cost' => $costPrice,
                'price' => round($sellingPrice, -3),
                'supplier_id' => $supplierId,
                'uom_id' => $uomId,
            ];
        }

        $this->command->info('Created ' . count($this->products) . ' products');
    }

    protected function seedTransactionsChronologically(): void
    {
        $this->command->info('Seeding transactions chronologically...');

        $currentDate = $this->startDate->copy();
        $poCounter = 0;
        $soCounter = 0;
        $purchaseReturnCounter = 0;
        $salesReturnCounter = 0;

        // First, create opening stock through initial POs in Feb 2025
        $this->createOpeningStock($currentDate);

        while ($currentDate->lte($this->endDate)) {
            $month = $currentDate->month;
            $dayOfWeek = $currentDate->dayOfWeek;
            $multiplier = $this->seasonalityMultipliers[$month];

            // Reduce weekend activity
            if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
                $multiplier *= 0.4;
            }

            // Calculate daily targets
            $baseSalesPerDay = 3; // Average 3 sales orders per day
            $basePurchasesPerDay = 0.6; // Average ~18 POs per month

            $salesTarget = max(0, (int) round($baseSalesPerDay * $multiplier + (rand(-1, 1) * 0.5)));
            $purchaseTarget = rand(0, 100) < ($basePurchasesPerDay * 100) ? 1 : 0;

            // Create Purchase Orders (to replenish stock)
            for ($i = 0; $i < $purchaseTarget; $i++) {
                $this->createPurchaseOrder($currentDate->copy(), $poCounter++);
            }

            // Create Sales Orders
            for ($i = 0; $i < $salesTarget; $i++) {
                $this->createSalesOrder($currentDate->copy(), $soCounter++);
            }

            // Occasionally create returns (about 5% of days)
            if (rand(0, 100) < 5 && $soCounter > 10) {
                $this->createSalesReturn($currentDate->copy(), $salesReturnCounter++);
            }

            if (rand(0, 100) < 3 && $poCounter > 5) {
                $this->createPurchaseReturn($currentDate->copy(), $purchaseReturnCounter++);
            }

            $currentDate->addDay();

            // Progress indicator
            if ($currentDate->day === 1) {
                $this->command->info("Processing {$currentDate->format('F Y')}... (POs: {$poCounter}, SOs: {$soCounter})");
            }
        }

        $this->command->info("Created {$poCounter} purchase orders");
        $this->command->info("Created {$soCounter} sales orders");
        $this->command->info("Created {$purchaseReturnCounter} purchase returns");
        $this->command->info("Created {$salesReturnCounter} sales returns");
    }

    protected function createOpeningStock(Carbon $date): void
    {
        $this->command->info('Creating opening stock...');

        // Create larger initial POs to establish stock
        $productsToStock = collect($this->products)->shuffle()->take(count($this->products));

        // Group products by supplier
        $productsBySupplier = $productsToStock->groupBy('supplier_id');

        $initCounter = 0;
        foreach ($productsBySupplier as $supplierId => $products) {
            if (!$supplierId) continue;

            $initCounter++;
            $poNumber = 'PO-' . $date->format('Y') . '-I' . str_pad($initCounter, 4, '0', STR_PAD_LEFT);
            $orderDate = $date->copy()->addDays(rand(0, 5));

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $supplierId,
                'order_date' => $orderDate,
                'expected_date' => $orderDate->copy()->addDays(rand(3, 7)),
                'status' => 'received',
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'subtotal' => 0,
                'discount_type' => 'percentage',
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_enabled' => true,
                'tax_name' => 'PPN',
                'tax_percentage' => 11,
                'tax_amount' => 0,
                'grand_total' => 0,
                'payment_terms' => 'Net 30',
                'payment_status' => 'paid',
                'amount_paid' => 0,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $subtotal = 0;

            foreach ($products as $product) {
                $quantity = rand(50, 200);
                $unitCost = $product['cost'];
                $lineSubtotal = $quantity * $unitCost;
                $subtotal += $lineSubtotal;

                $poItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product['id'],
                    'uom_id' => $product['uom_id'],
                    'quantity' => $quantity,
                    'quantity_received' => $quantity,
                    'unit_cost' => $unitCost,
                    'discount_percentage' => 0,
                    'subtotal' => $lineSubtotal,
                ]);

                // Update inventory
                $this->addToInventory($product['id'], $quantity, $unitCost, $orderDate, 'opening_stock', 'PurchaseOrder', $po->id);
            }

            // Calculate totals
            $taxAmount = $subtotal * 0.11;
            $grandTotal = $subtotal + $taxAmount;

            $po->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'amount_paid' => $grandTotal,
            ]);

            // Create receiving record
            $receiving = PurchaseReceiving::create([
                'receiving_number' => 'RCV-' . $date->format('Y') . '-I' . str_pad($initCounter, 4, '0', STR_PAD_LEFT),
                'purchase_order_id' => $po->id,
                'receiving_date' => $orderDate->copy()->addDays(rand(3, 7)),
                'status' => 'confirmed',
                'created_by' => 1,
            ]);

            // Create payment record
            Payment::create([
                'payment_number' => 'PAY-' . $date->format('Y') . '-I' . str_pad($initCounter, 4, '0', STR_PAD_LEFT),
                'payment_type' => 'purchase',
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'payment_date' => $orderDate->copy()->addDays(rand(7, 14)),
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => $grandTotal,
                'amount_in_base' => $grandTotal,
                'payment_method' => 'bank_transfer',
                'status' => 'confirmed',
                'created_by' => 1,
            ]);
        }
    }

    protected function createPurchaseOrder(Carbon $date, int $counter): void
    {
        // Select a random supplier
        $supplier = $this->suppliers[array_rand($this->suppliers)];

        // Get products from this supplier
        $supplierProducts = collect($this->products)
            ->filter(fn($p) => $p['supplier_id'] === $supplier['id'])
            ->values()
            ->toArray();

        if (empty($supplierProducts)) {
            $supplierProducts = array_slice($this->products, 0, 10);
        }

        // Select 2-6 products for this PO
        $selectedProducts = collect($supplierProducts)->shuffle()->take(rand(2, 6))->toArray();

        $poNumber = 'PO-' . $date->format('Y') . '-' . str_pad($counter + 100, 5, '0', STR_PAD_LEFT);
        $orderDate = $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59));

        // Determine status and payment status
        $statusRoll = rand(1, 100);
        if ($statusRoll <= 85) {
            $status = 'received';
        } elseif ($statusRoll <= 92) {
            $status = 'partial';
        } elseif ($statusRoll <= 97) {
            $status = 'confirmed';
        } else {
            $status = 'cancelled';
        }

        // Apply discount occasionally
        $discountRoll = rand(1, 100);
        $discountValue = 0;
        if ($discountRoll <= 15) {
            $discountValue = rand(1, 2) * 5; // 5% or 10%
        }

        $po = PurchaseOrder::create([
            'po_number' => $poNumber,
            'supplier_id' => $supplier['id'],
            'order_date' => $orderDate,
            'expected_date' => $orderDate->copy()->addDays(rand(5, 14)),
            'status' => $status,
            'currency_code' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 0,
            'discount_type' => 'percentage',
            'discount_value' => $discountValue,
            'discount_amount' => 0,
            'tax_enabled' => true,
            'tax_name' => 'PPN',
            'tax_percentage' => 11,
            'tax_amount' => 0,
            'grand_total' => 0,
            'payment_terms' => collect(['Net 15', 'Net 30', 'Net 45', 'COD'])->random(),
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $orderDate,
            'updated_at' => $orderDate,
        ]);

        $subtotal = 0;
        $poItems = [];

        foreach ($selectedProducts as $product) {
            $quantity = rand(10, 100);
            $unitCost = $product['cost'] * (1 + rand(-5, 5) / 100); // +/- 5% cost variation
            $lineDiscount = rand(0, 100) < 20 ? rand(1, 2) * 5 : 0;
            $lineSubtotal = $quantity * $unitCost * (1 - $lineDiscount / 100);
            $subtotal += $lineSubtotal;

            $qtyReceived = 0;
            if ($status === 'received') {
                $qtyReceived = $quantity;
            } elseif ($status === 'partial') {
                $qtyReceived = (int) ($quantity * rand(30, 80) / 100);
            }

            $poItem = PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'product_id' => $product['id'],
                'uom_id' => $product['uom_id'],
                'quantity' => $quantity,
                'quantity_received' => $qtyReceived,
                'unit_cost' => $unitCost,
                'discount_percentage' => $lineDiscount,
                'subtotal' => $lineSubtotal,
            ]);

            $poItems[] = [
                'item' => $poItem,
                'product' => $product,
                'qty_received' => $qtyReceived,
                'unit_cost' => $unitCost,
            ];

            // Update inventory if received
            if ($qtyReceived > 0) {
                $this->addToInventory($product['id'], $qtyReceived, $unitCost, $orderDate, 'purchase_in', 'PurchaseOrder', $po->id);
            }
        }

        // Calculate totals
        $discountAmount = $subtotal * ($discountValue / 100);
        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * 0.11;
        $grandTotal = $afterDiscount + $taxAmount;

        // Determine payment
        $paymentStatus = 'unpaid';
        $amountPaid = 0;

        if ($status !== 'cancelled' && $status !== 'draft') {
            $paymentRoll = rand(1, 100);
            if ($paymentRoll <= 75) {
                $paymentStatus = 'paid';
                $amountPaid = $grandTotal;
            } elseif ($paymentRoll <= 90) {
                $paymentStatus = 'partial';
                $amountPaid = $grandTotal * rand(30, 70) / 100;
            }
        }

        $po->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'payment_status' => $paymentStatus,
            'amount_paid' => $amountPaid,
        ]);

        // Create receiving record if applicable
        if (in_array($status, ['received', 'partial'])) {
            $receivingDate = $orderDate->copy()->addDays(rand(3, 10));

            $receiving = PurchaseReceiving::create([
                'receiving_number' => 'RCV-' . $receivingDate->format('Y') . '-' . str_pad($counter + 100, 5, '0', STR_PAD_LEFT),
                'purchase_order_id' => $po->id,
                'receiving_date' => $receivingDate,
                'status' => 'confirmed',
                'created_by' => 1,
                'created_at' => $receivingDate,
                'updated_at' => $receivingDate,
            ]);

            foreach ($poItems as $poItemData) {
                if ($poItemData['qty_received'] > 0) {
                    PurchaseReceivingItem::create([
                        'purchase_receiving_id' => $receiving->id,
                        'purchase_order_item_id' => $poItemData['item']->id,
                        'product_id' => $poItemData['product']['id'],
                        'quantity_received' => $poItemData['qty_received'],
                        'unit_cost' => $poItemData['unit_cost'],
                    ]);
                }
            }
        }

        // Create payment record if applicable
        if ($amountPaid > 0) {
            $paymentDate = $orderDate->copy()->addDays(rand(7, 30));
            $paymentMethods = ['bank_transfer', 'bank_transfer', 'bank_transfer', 'cheque', 'giro'];

            Payment::create([
                'payment_number' => 'PAY-' . $paymentDate->format('Y') . '-P' . str_pad($counter + 100, 5, '0', STR_PAD_LEFT),
                'payment_type' => 'purchase',
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'payment_date' => $paymentDate,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => $amountPaid,
                'amount_in_base' => $amountPaid,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'status' => 'confirmed',
                'created_by' => 1,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
        }
    }

    protected function createSalesOrder(Carbon $date, int $counter): void
    {
        // Select a random customer
        $customer = $this->customers[array_rand($this->customers)];

        // Select 1-5 products with available stock
        $availableProducts = collect($this->products)->filter(function ($p) {
            $stock = InventoryStock::where('product_id', $p['id'])->first();
            return $stock && $stock->quantity_available > 5;
        })->values()->toArray();

        if (count($availableProducts) < 3) {
            return; // Skip if not enough stock
        }

        $selectedProducts = collect($availableProducts)->shuffle()->take(rand(1, 5))->toArray();

        $soNumber = 'SO-' . $date->format('Y') . '-' . str_pad($counter + 1000, 5, '0', STR_PAD_LEFT);
        $orderDate = $date->copy()->addHours(rand(8, 20))->addMinutes(rand(0, 59));

        // Determine status
        $statusRoll = rand(1, 100);
        if ($statusRoll <= 80) {
            $status = 'delivered';
        } elseif ($statusRoll <= 88) {
            $status = 'shipped';
        } elseif ($statusRoll <= 93) {
            $status = 'processing';
        } elseif ($statusRoll <= 97) {
            $status = 'confirmed';
        } else {
            $status = 'cancelled';
        }

        // Apply discount occasionally
        $discountRoll = rand(1, 100);
        $discountValue = 0;
        if ($discountRoll <= 20) {
            $discountValue = rand(1, 3) * 5; // 5%, 10%, or 15%
        } elseif ($discountRoll <= 25) {
            $discountValue = rand(4, 6) * 5; // 20%, 25%, or 30%
        }

        $shippingFee = collect([0, 0, 15000, 20000, 25000, 30000, 50000])->random();

        $so = SalesOrder::create([
            'so_number' => $soNumber,
            'customer_id' => $customer['id'],
            'order_date' => $orderDate,
            'due_date' => $orderDate->copy()->addDays(rand(7, 30)),
            'status' => $status,
            'currency_code' => 'IDR',
            'exchange_rate' => 1,
            'subtotal' => 0,
            'discount_type' => 'percentage',
            'discount_value' => $discountValue,
            'discount_amount' => 0,
            'tax_enabled' => true,
            'tax_name' => 'PPN',
            'tax_percentage' => 11,
            'tax_amount' => 0,
            'shipping_fee' => $shippingFee,
            'shipping_address' => $customer['city'] . ', Indonesia',
            'grand_total' => 0,
            'payment_terms' => collect(['COD', 'Net 7', 'Net 14', 'Net 30'])->random(),
            'payment_status' => 'unpaid',
            'amount_paid' => 0,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $orderDate,
            'updated_at' => $orderDate,
        ]);

        $subtotal = 0;
        $soItems = [];

        foreach ($selectedProducts as $product) {
            $stock = InventoryStock::where('product_id', $product['id'])->first();
            $maxQty = min((int) $stock->quantity_available, 20);
            if ($maxQty < 1) continue;

            $quantity = rand(1, $maxQty);
            $unitPrice = $product['price'] * (1 + rand(-10, 10) / 100); // +/- 10% price variation
            $lineDiscount = rand(0, 100) < 15 ? rand(1, 2) * 5 : 0;
            $lineSubtotal = $quantity * $unitPrice * (1 - $lineDiscount / 100);
            $subtotal += $lineSubtotal;

            $qtyShipped = 0;
            if (in_array($status, ['delivered', 'shipped'])) {
                $qtyShipped = $quantity;
            } elseif ($status === 'partial') {
                $qtyShipped = (int) ($quantity * rand(30, 80) / 100);
            }

            $soItem = SalesOrderItem::create([
                'sales_order_id' => $so->id,
                'product_id' => $product['id'],
                'uom_id' => $product['uom_id'],
                'quantity' => $quantity,
                'quantity_shipped' => $qtyShipped,
                'unit_price' => $unitPrice,
                'unit_cost' => $product['cost'],
                'discount_percentage' => $lineDiscount,
                'subtotal' => $lineSubtotal,
            ]);

            $soItems[] = [
                'item' => $soItem,
                'product' => $product,
                'qty_shipped' => $qtyShipped,
            ];

            // Deduct from inventory if shipped/delivered
            if ($qtyShipped > 0 && $status !== 'cancelled') {
                $this->deductFromInventory($product['id'], $qtyShipped, $orderDate, 'sales_out', 'SalesOrder', $so->id);
            }
        }

        if ($subtotal === 0) {
            $so->delete();
            return;
        }

        // Calculate totals
        $discountAmount = $subtotal * ($discountValue / 100);
        $afterDiscount = $subtotal - $discountAmount;
        $taxAmount = $afterDiscount * 0.11;
        $grandTotal = $afterDiscount + $taxAmount + $shippingFee;

        // Determine payment
        $paymentStatus = 'unpaid';
        $amountPaid = 0;

        if ($status !== 'cancelled' && $status !== 'draft') {
            $paymentRoll = rand(1, 100);
            if ($paymentRoll <= 80) {
                $paymentStatus = 'paid';
                $amountPaid = $grandTotal;
            } elseif ($paymentRoll <= 92) {
                $paymentStatus = 'partial';
                $amountPaid = $grandTotal * rand(40, 80) / 100;
            }
        }

        $so->update([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
            'payment_status' => $paymentStatus,
            'amount_paid' => $amountPaid,
        ]);

        // Create shipment record if applicable
        if (in_array($status, ['delivered', 'shipped', 'in_transit'])) {
            $shipmentDate = $orderDate->copy()->addDays(rand(1, 5));
            $couriers = ['JNE', 'J&T', 'SiCepat', 'AnterAja', 'Ninja Express', 'GoSend', 'GrabExpress'];

            $shipment = SalesShipment::create([
                'shipment_number' => 'SHP-' . $shipmentDate->format('Y') . '-' . str_pad($counter + 1000, 5, '0', STR_PAD_LEFT),
                'sales_order_id' => $so->id,
                'shipment_date' => $shipmentDate,
                'courier' => $couriers[array_rand($couriers)],
                'tracking_number' => strtoupper(Str::random(12)),
                'status' => $status === 'delivered' ? 'delivered' : 'shipped',
                'created_by' => 1,
                'created_at' => $shipmentDate,
                'updated_at' => $shipmentDate,
            ]);

            foreach ($soItems as $soItemData) {
                if ($soItemData['qty_shipped'] > 0) {
                    SalesShipmentItem::create([
                        'sales_shipment_id' => $shipment->id,
                        'sales_order_item_id' => $soItemData['item']->id,
                        'product_id' => $soItemData['product']['id'],
                        'quantity_shipped' => $soItemData['qty_shipped'],
                    ]);
                }
            }
        }

        // Create payment record if applicable
        if ($amountPaid > 0) {
            $paymentDate = $orderDate->copy()->addDays(rand(0, 14));
            $paymentMethods = ['bank_transfer', 'bank_transfer', 'cash', 'e_wallet', 'credit_card', 'debit_card'];

            Payment::create([
                'payment_number' => 'PAY-' . $paymentDate->format('Y') . '-S' . str_pad($counter + 1000, 5, '0', STR_PAD_LEFT),
                'payment_type' => 'sales',
                'reference_type' => 'sales_order',
                'reference_id' => $so->id,
                'payment_date' => $paymentDate,
                'currency_code' => 'IDR',
                'exchange_rate' => 1,
                'amount' => $amountPaid,
                'amount_in_base' => $amountPaid,
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'status' => 'confirmed',
                'created_by' => 1,
                'created_at' => $paymentDate,
                'updated_at' => $paymentDate,
            ]);
        }
    }

    protected function createSalesReturn(Carbon $date, int $counter): void
    {
        // Find a delivered sales order to return
        $so = SalesOrder::where('status', 'delivered')
            ->where('order_date', '<', $date)
            ->where('order_date', '>', $date->copy()->subMonths(2))
            ->inRandomOrder()
            ->first();

        if (!$so) return;

        $returnDate = $date->copy()->addHours(rand(8, 17));
        $reasons = ['Defective product', 'Wrong item received', 'Product damaged', 'Customer changed mind', 'Not as described'];
        $refundMethods = ['credit_note', 'cash_refund', 'replacement', 'credit_note'];
        $conditions = ['good', 'good', 'damaged', 'damaged', 'expired'];

        $return = SalesReturn::create([
            'return_number' => 'SR-' . $returnDate->format('Y') . '-' . str_pad($counter + 1, 5, '0', STR_PAD_LEFT),
            'sales_order_id' => $so->id,
            'customer_id' => $so->customer_id,
            'return_date' => $returnDate,
            'currency_code' => 'IDR',
            'exchange_rate' => 1,
            'status' => 'completed',
            'subtotal' => 0,
            'refund_method' => $refundMethods[array_rand($refundMethods)],
            'reason' => $reasons[array_rand($reasons)],
            'created_by' => 1,
            'created_at' => $returnDate,
            'updated_at' => $returnDate,
        ]);

        $subtotal = 0;
        $soItems = SalesOrderItem::where('sales_order_id', $so->id)->get();

        // Return 1-2 items
        $itemsToReturn = $soItems->shuffle()->take(rand(1, min(2, $soItems->count())));

        foreach ($itemsToReturn as $soItem) {
            $returnQty = rand(1, max(1, (int) ($soItem->quantity * 0.5)));
            $condition = $conditions[array_rand($conditions)];
            $restock = $condition === 'good' ? true : false;

            $lineSubtotal = $returnQty * $soItem->unit_price;
            $subtotal += $lineSubtotal;

            SalesReturnItem::create([
                'sales_return_id' => $return->id,
                'product_id' => $soItem->product_id,
                'uom_id' => $soItem->uom_id,
                'quantity' => $returnQty,
                'unit_price' => $soItem->unit_price,
                'unit_cost' => $soItem->unit_cost,
                'subtotal' => $lineSubtotal,
                'reason' => $reasons[array_rand($reasons)],
                'condition' => $condition,
                'restock' => $restock,
            ]);

            // Add back to inventory if restocking
            if ($restock) {
                $this->addToInventory($soItem->product_id, $returnQty, $soItem->unit_cost, $returnDate, 'sales_return', 'SalesReturn', $return->id);
            }
        }

        $return->update(['subtotal' => $subtotal]);
    }

    protected function createPurchaseReturn(Carbon $date, int $counter): void
    {
        // Find a received purchase order to return
        $po = PurchaseOrder::where('status', 'received')
            ->where('order_date', '<', $date)
            ->where('order_date', '>', $date->copy()->subMonths(2))
            ->inRandomOrder()
            ->first();

        if (!$po) return;

        $returnDate = $date->copy()->addHours(rand(8, 17));
        $reasons = ['Defective items', 'Wrong items delivered', 'Damaged in transit', 'Quality not as specified', 'Overshipment'];

        $return = PurchaseReturn::create([
            'return_number' => 'PR-' . $returnDate->format('Y') . '-' . str_pad($counter + 1, 5, '0', STR_PAD_LEFT),
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'return_date' => $returnDate,
            'currency_code' => 'IDR',
            'exchange_rate' => 1,
            'status' => 'completed',
            'subtotal' => 0,
            'reason' => $reasons[array_rand($reasons)],
            'created_by' => 1,
            'created_at' => $returnDate,
            'updated_at' => $returnDate,
        ]);

        $subtotal = 0;
        $poItems = PurchaseOrderItem::where('purchase_order_id', $po->id)->get();

        // Return 1-2 items
        $itemsToReturn = $poItems->shuffle()->take(rand(1, min(2, $poItems->count())));

        foreach ($itemsToReturn as $poItem) {
            $returnQty = rand(1, max(1, (int) ($poItem->quantity_received * 0.3)));
            $lineSubtotal = $returnQty * $poItem->unit_cost;
            $subtotal += $lineSubtotal;

            PurchaseReturnItem::create([
                'purchase_return_id' => $return->id,
                'product_id' => $poItem->product_id,
                'uom_id' => $poItem->uom_id,
                'quantity' => $returnQty,
                'unit_cost' => $poItem->unit_cost,
                'subtotal' => $lineSubtotal,
                'reason' => $reasons[array_rand($reasons)],
            ]);

            // Deduct from inventory
            $this->deductFromInventory($poItem->product_id, $returnQty, $returnDate, 'purchase_return', 'PurchaseReturn', $return->id);
        }

        $return->update(['subtotal' => $subtotal]);
    }

    protected function addToInventory(int $productId, float $quantity, float $unitCost, Carbon $date, string $movementType, string $refType, int $refId): void
    {
        $stock = InventoryStock::where('product_id', $productId)->first();

        if (!$stock) {
            $stock = InventoryStock::create([
                'product_id' => $productId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
                'average_cost' => $unitCost,
                'last_cost' => $unitCost,
            ]);
        }

        $qtyBefore = $stock->quantity_on_hand;
        $qtyAfter = $qtyBefore + $quantity;

        // Update average cost
        $totalValue = ($stock->quantity_on_hand * $stock->average_cost) + ($quantity * $unitCost);
        $newAvgCost = $qtyAfter > 0 ? $totalValue / $qtyAfter : $unitCost;

        $stock->update([
            'quantity_on_hand' => $qtyAfter,
            'quantity_available' => $qtyAfter - $stock->quantity_reserved,
            'average_cost' => $newAvgCost,
            'last_cost' => $unitCost,
            'last_movement_at' => $date,
        ]);

        // Update product stock_quantity
        Product::where('id', $productId)->update(['stock_quantity' => $qtyAfter]);

        // Record movement
        InventoryMovement::create([
            'movement_date' => $date,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'quantity_before' => $qtyBefore,
            'quantity_after' => $qtyAfter,
            'created_by' => 1,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    protected function deductFromInventory(int $productId, float $quantity, Carbon $date, string $movementType, string $refType, int $refId): void
    {
        $stock = InventoryStock::where('product_id', $productId)->first();

        if (!$stock) return;

        $qtyBefore = $stock->quantity_on_hand;
        $qtyAfter = max(0, $qtyBefore - $quantity);

        $stock->update([
            'quantity_on_hand' => $qtyAfter,
            'quantity_available' => max(0, $qtyAfter - $stock->quantity_reserved),
            'last_movement_at' => $date,
        ]);

        // Update product stock_quantity
        Product::where('id', $productId)->update(['stock_quantity' => $qtyAfter]);

        // Record movement
        InventoryMovement::create([
            'movement_date' => $date,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'quantity' => -$quantity,
            'unit_cost' => $stock->average_cost,
            'quantity_before' => $qtyBefore,
            'quantity_after' => $qtyAfter,
            'created_by' => 1,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    protected function getProductDefinitions(): array
    {
        return [
            // Electronics - Smartphones
            ['name' => 'Samsung Galaxy S24 Ultra 256GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'cost' => 15000000],
            ['name' => 'Samsung Galaxy S24+ 256GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'cost' => 12000000],
            ['name' => 'Samsung Galaxy A54 5G 128GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'cost' => 4000000],
            ['name' => 'Samsung Galaxy A34 5G 128GB', 'brand' => 'Samsung', 'category' => 'Smartphones', 'cost' => 3200000],
            ['name' => 'iPhone 15 Pro Max 256GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'cost' => 18000000],
            ['name' => 'iPhone 15 Pro 128GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'cost' => 15000000],
            ['name' => 'iPhone 15 128GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'cost' => 11000000],
            ['name' => 'iPhone 14 128GB', 'brand' => 'Apple', 'category' => 'Smartphones', 'cost' => 9500000],
            ['name' => 'Xiaomi 14 Ultra 512GB', 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'cost' => 12000000],
            ['name' => 'Xiaomi 14 256GB', 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'cost' => 7500000],
            ['name' => 'Xiaomi Redmi Note 13 Pro 256GB', 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'cost' => 3000000],
            ['name' => 'Xiaomi Redmi 13C 128GB', 'brand' => 'Xiaomi', 'category' => 'Smartphones', 'cost' => 1500000],
            ['name' => 'Sony Xperia 1 V 256GB', 'brand' => 'Sony', 'category' => 'Smartphones', 'cost' => 14000000],

            // Electronics - Laptops
            ['name' => 'MacBook Pro 14" M3 Pro 512GB', 'brand' => 'Apple', 'category' => 'Laptops & Computers', 'cost' => 28000000],
            ['name' => 'MacBook Air 13" M3 256GB', 'brand' => 'Apple', 'category' => 'Laptops & Computers', 'cost' => 15000000],
            ['name' => 'ASUS ROG Strix G16 RTX4060', 'brand' => 'Asus', 'category' => 'Laptops & Computers', 'cost' => 18000000],
            ['name' => 'ASUS Vivobook 15 i5 512GB', 'brand' => 'Asus', 'category' => 'Laptops & Computers', 'cost' => 8000000],
            ['name' => 'ASUS Zenbook 14 OLED', 'brand' => 'Asus', 'category' => 'Laptops & Computers', 'cost' => 14000000],

            // Electronics - Audio
            ['name' => 'Sony WH-1000XM5 Headphones', 'brand' => 'Sony', 'category' => 'Audio & Headphones', 'cost' => 3500000],
            ['name' => 'Sony WF-1000XM5 Earbuds', 'brand' => 'Sony', 'category' => 'Audio & Headphones', 'cost' => 2800000],
            ['name' => 'JBL Tune 770NC Headphones', 'brand' => 'JBL', 'category' => 'Audio & Headphones', 'cost' => 1200000],
            ['name' => 'JBL Flip 6 Bluetooth Speaker', 'brand' => 'JBL', 'category' => 'Audio & Headphones', 'cost' => 1500000],
            ['name' => 'JBL PartyBox 110', 'brand' => 'JBL', 'category' => 'Audio & Headphones', 'cost' => 4500000],
            ['name' => 'Apple AirPods Pro 2nd Gen', 'brand' => 'Apple', 'category' => 'Audio & Headphones', 'cost' => 3200000],
            ['name' => 'Apple AirPods 3rd Gen', 'brand' => 'Apple', 'category' => 'Audio & Headphones', 'cost' => 2200000],
            ['name' => 'Samsung Galaxy Buds2 Pro', 'brand' => 'Samsung', 'category' => 'Audio & Headphones', 'cost' => 1800000],

            // Electronics - Accessories
            ['name' => 'Logitech MX Master 3S Mouse', 'brand' => 'Logitech', 'category' => 'Accessories', 'cost' => 1200000],
            ['name' => 'Logitech MX Keys Keyboard', 'brand' => 'Logitech', 'category' => 'Accessories', 'cost' => 1500000],
            ['name' => 'Logitech C920 HD Webcam', 'brand' => 'Logitech', 'category' => 'Accessories', 'cost' => 800000],
            ['name' => 'Samsung 65W Travel Adapter', 'brand' => 'Samsung', 'category' => 'Accessories', 'cost' => 350000],
            ['name' => 'Apple MagSafe Charger', 'brand' => 'Apple', 'category' => 'Accessories', 'cost' => 550000],
            ['name' => 'Apple 20W USB-C Power Adapter', 'brand' => 'Apple', 'category' => 'Accessories', 'cost' => 280000],

            // Electronics - Gaming
            ['name' => 'ASUS ROG Ally Gaming Handheld', 'brand' => 'Asus', 'category' => 'Gaming', 'cost' => 8500000],
            ['name' => 'Logitech G Pro X Superlight Mouse', 'brand' => 'Logitech', 'category' => 'Gaming', 'cost' => 1800000],
            ['name' => 'Logitech G915 TKL Keyboard', 'brand' => 'Logitech', 'category' => 'Gaming', 'cost' => 2500000],

            // Fashion - Men's
            ['name' => 'Nike Air Force 1 Low White', 'brand' => 'Nike', 'category' => 'Footwear', 'cost' => 1100000],
            ['name' => 'Nike Air Max 90', 'brand' => 'Nike', 'category' => 'Footwear', 'cost' => 1400000],
            ['name' => 'Nike Dunk Low Retro', 'brand' => 'Nike', 'category' => 'Footwear', 'cost' => 1300000],
            ['name' => 'Nike Dri-FIT Running Shirt', 'brand' => 'Nike', 'category' => 'Men\'s Clothing', 'cost' => 350000],
            ['name' => 'Nike Tech Fleece Joggers', 'brand' => 'Nike', 'category' => 'Men\'s Clothing', 'cost' => 850000],
            ['name' => 'Adidas Ultraboost Light', 'brand' => 'Adidas', 'category' => 'Footwear', 'cost' => 2000000],
            ['name' => 'Adidas Stan Smith', 'brand' => 'Adidas', 'category' => 'Footwear', 'cost' => 1200000],
            ['name' => 'Adidas Originals Trefoil Hoodie', 'brand' => 'Adidas', 'category' => 'Men\'s Clothing', 'cost' => 700000],
            ['name' => 'Adidas 3-Stripes Pants', 'brand' => 'Adidas', 'category' => 'Men\'s Clothing', 'cost' => 500000],
            ['name' => 'Uniqlo AIRism Cotton T-Shirt', 'brand' => 'Uniqlo', 'category' => 'Men\'s Clothing', 'cost' => 150000],
            ['name' => 'Uniqlo Ultra Light Down Jacket', 'brand' => 'Uniqlo', 'category' => 'Men\'s Clothing', 'cost' => 600000],
            ['name' => 'Uniqlo Slim Fit Chino Pants', 'brand' => 'Uniqlo', 'category' => 'Men\'s Clothing', 'cost' => 350000],

            // Fashion - Women's
            ['name' => 'Nike Air Zoom Pegasus 40 Women', 'brand' => 'Nike', 'category' => 'Footwear', 'cost' => 1500000],
            ['name' => 'Nike Sportswear Essential Leggings', 'brand' => 'Nike', 'category' => 'Women\'s Clothing', 'cost' => 450000],
            ['name' => 'Adidas NMD_R1 Women', 'brand' => 'Adidas', 'category' => 'Footwear', 'cost' => 1600000],
            ['name' => 'H&M Oversized Blazer', 'brand' => 'H&M', 'category' => 'Women\'s Clothing', 'cost' => 400000],
            ['name' => 'H&M Wide-Leg Trousers', 'brand' => 'H&M', 'category' => 'Women\'s Clothing', 'cost' => 280000],
            ['name' => 'H&M Ribbed Jersey Dress', 'brand' => 'H&M', 'category' => 'Women\'s Clothing', 'cost' => 200000],
            ['name' => 'Zara Satin Effect Blouse', 'brand' => 'Zara', 'category' => 'Women\'s Clothing', 'cost' => 350000],
            ['name' => 'Zara High Waist Jeans', 'brand' => 'Zara', 'category' => 'Women\'s Clothing', 'cost' => 500000],
            ['name' => 'Uniqlo Ultra Stretch Leggings Pants', 'brand' => 'Uniqlo', 'category' => 'Women\'s Clothing', 'cost' => 300000],

            // Fashion - Bags
            ['name' => 'Nike Brasilia Backpack', 'brand' => 'Nike', 'category' => 'Bags & Accessories', 'cost' => 450000],
            ['name' => 'Adidas Classic Badge Backpack', 'brand' => 'Adidas', 'category' => 'Bags & Accessories', 'cost' => 400000],
            ['name' => 'Uniqlo Lightweight Shoulder Bag', 'brand' => 'Uniqlo', 'category' => 'Bags & Accessories', 'cost' => 200000],

            // Home & Living - Furniture
            ['name' => 'IKEA MALM Bed Frame Queen', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 2500000],
            ['name' => 'IKEA KALLAX Shelf Unit 4x4', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 1800000],
            ['name' => 'IKEA POÄNG Armchair', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 1500000],
            ['name' => 'IKEA LACK Coffee Table', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 350000],
            ['name' => 'IKEA BILLY Bookcase', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 700000],
            ['name' => 'Informa Office Chair Ergonomic', 'brand' => 'Informa', 'category' => 'Furniture', 'cost' => 2000000],
            ['name' => 'Informa Study Desk 120cm', 'brand' => 'Informa', 'category' => 'Furniture', 'cost' => 1200000],

            // Home & Living - Kitchen
            ['name' => 'IKEA 365+ Food Container Set', 'brand' => 'IKEA', 'category' => 'Kitchen & Dining', 'cost' => 180000],
            ['name' => 'IKEA VARDAGEN Frying Pan 28cm', 'brand' => 'IKEA', 'category' => 'Kitchen & Dining', 'cost' => 250000],
            ['name' => 'ACE Stainless Steel Pot Set', 'brand' => 'ACE', 'category' => 'Kitchen & Dining', 'cost' => 800000],
            ['name' => 'ACE Kitchen Knife Set 6pcs', 'brand' => 'ACE', 'category' => 'Kitchen & Dining', 'cost' => 450000],

            // Home & Living - Decor & Bedding
            ['name' => 'IKEA FEJKA Artificial Plant', 'brand' => 'IKEA', 'category' => 'Decor', 'cost' => 80000],
            ['name' => 'IKEA BRIMNES Mirror', 'brand' => 'IKEA', 'category' => 'Decor', 'cost' => 400000],
            ['name' => 'IKEA DVALA Bed Sheet Set Queen', 'brand' => 'IKEA', 'category' => 'Bedding', 'cost' => 350000],
            ['name' => 'IKEA GRUSNARV Mattress Protector', 'brand' => 'IKEA', 'category' => 'Bedding', 'cost' => 200000],

            // Home & Living - Storage
            ['name' => 'IKEA SKUBB Storage Box Set', 'brand' => 'IKEA', 'category' => 'Storage', 'cost' => 150000],
            ['name' => 'ACE Plastic Storage Container 50L', 'brand' => 'ACE', 'category' => 'Storage', 'cost' => 120000],

            // Food & Beverages
            ['name' => 'Nestle Milo 1kg', 'brand' => 'Nestle', 'category' => 'Beverages', 'cost' => 95000],
            ['name' => 'Nestle Dancow 1+ 800g', 'brand' => 'Nestle', 'category' => 'Dairy', 'cost' => 120000],
            ['name' => 'Nestle KitKat 4F 35g x 24', 'brand' => 'Nestle', 'category' => 'Snacks', 'cost' => 150000],
            ['name' => 'Nestle Nescafe Classic 200g', 'brand' => 'Nestle', 'category' => 'Beverages', 'cost' => 55000],
            ['name' => 'Nestle Bear Brand Gold 140ml x 30', 'brand' => 'Nestle', 'category' => 'Dairy', 'cost' => 280000],
            ['name' => 'Indofood Indomie Goreng 85g x 40', 'brand' => 'Indofood', 'category' => 'Pantry Staples', 'cost' => 110000],
            ['name' => 'Indofood Sambal ABC 335ml', 'brand' => 'Indofood', 'category' => 'Pantry Staples', 'cost' => 18000],
            ['name' => 'Indofood Kecap Manis ABC 620ml', 'brand' => 'Indofood', 'category' => 'Pantry Staples', 'cost' => 22000],
            ['name' => 'Indofood Bumbu Racik Opor 45g x 20', 'brand' => 'Indofood', 'category' => 'Pantry Staples', 'cost' => 60000],
            ['name' => 'Indofood Chitato 68g x 20', 'brand' => 'Indofood', 'category' => 'Snacks', 'cost' => 180000],

            // Health & Beauty - Skincare
            ['name' => 'L\'Oreal Revitalift Laser X3 Day Cream', 'brand' => 'L\'Oreal', 'category' => 'Skincare', 'cost' => 200000],
            ['name' => 'L\'Oreal UV Perfect SPF50 30ml', 'brand' => 'L\'Oreal', 'category' => 'Skincare', 'cost' => 150000],
            ['name' => 'Wardah Lightening Day Cream 30g', 'brand' => 'Wardah', 'category' => 'Skincare', 'cost' => 35000],
            ['name' => 'Wardah Aloe Vera Gel 100ml', 'brand' => 'Wardah', 'category' => 'Skincare', 'cost' => 28000],
            ['name' => 'Wardah Nature Daily Serum 30ml', 'brand' => 'Wardah', 'category' => 'Skincare', 'cost' => 55000],

            // Health & Beauty - Haircare
            ['name' => 'L\'Oreal Elseve Total Repair 5 Shampoo 680ml', 'brand' => 'L\'Oreal', 'category' => 'Haircare', 'cost' => 75000],
            ['name' => 'L\'Oreal Extraordinary Oil 100ml', 'brand' => 'L\'Oreal', 'category' => 'Haircare', 'cost' => 120000],
            ['name' => 'Unilever Clear Men Shampoo 320ml', 'brand' => 'Unilever', 'category' => 'Haircare', 'cost' => 38000],
            ['name' => 'Unilever Sunsilk Soft & Smooth 680ml', 'brand' => 'Unilever', 'category' => 'Haircare', 'cost' => 55000],

            // Health & Beauty - Makeup
            ['name' => 'Maybelline Fit Me Foundation 30ml', 'brand' => 'Maybelline', 'category' => 'Makeup', 'cost' => 95000],
            ['name' => 'Maybelline Superstay Matte Ink', 'brand' => 'Maybelline', 'category' => 'Makeup', 'cost' => 110000],
            ['name' => 'Maybelline Lash Sensational Mascara', 'brand' => 'Maybelline', 'category' => 'Makeup', 'cost' => 130000],
            ['name' => 'Maybelline Instant Age Rewind Concealer', 'brand' => 'Maybelline', 'category' => 'Makeup', 'cost' => 120000],
            ['name' => 'Wardah Exclusive Matte Lip Cream', 'brand' => 'Wardah', 'category' => 'Makeup', 'cost' => 50000],
            ['name' => 'Wardah Instaperfect Mineralight', 'brand' => 'Wardah', 'category' => 'Makeup', 'cost' => 85000],

            // Health & Beauty - Personal Care
            ['name' => 'Unilever Lifebuoy Bodywash 500ml', 'brand' => 'Unilever', 'category' => 'Personal Care', 'cost' => 35000],
            ['name' => 'Unilever Dove Body Lotion 400ml', 'brand' => 'Unilever', 'category' => 'Personal Care', 'cost' => 55000],
            ['name' => 'Unilever Rexona Deo Spray 150ml', 'brand' => 'Unilever', 'category' => 'Personal Care', 'cost' => 32000],
            ['name' => 'Unilever Pepsodent Toothpaste 190g', 'brand' => 'Unilever', 'category' => 'Personal Care', 'cost' => 18000],
            ['name' => 'Unilever Closeup Toothpaste 160g', 'brand' => 'Unilever', 'category' => 'Personal Care', 'cost' => 22000],

            // Additional products for variety
            ['name' => 'Samsung Galaxy Tab S9 256GB', 'brand' => 'Samsung', 'category' => 'Laptops & Computers', 'cost' => 9500000],
            ['name' => 'Apple iPad Air 5th Gen 64GB', 'brand' => 'Apple', 'category' => 'Laptops & Computers', 'cost' => 8500000],
            ['name' => 'Xiaomi Pad 6 128GB', 'brand' => 'Xiaomi', 'category' => 'Laptops & Computers', 'cost' => 4500000],
            ['name' => 'Samsung Galaxy Watch 6 44mm', 'brand' => 'Samsung', 'category' => 'Accessories', 'cost' => 3500000],
            ['name' => 'Apple Watch Series 9 45mm', 'brand' => 'Apple', 'category' => 'Accessories', 'cost' => 6500000],
            ['name' => 'Xiaomi Watch S3', 'brand' => 'Xiaomi', 'category' => 'Accessories', 'cost' => 1500000],
            ['name' => 'ASUS ProArt Monitor 27" 4K', 'brand' => 'Asus', 'category' => 'Laptops & Computers', 'cost' => 8000000],
            ['name' => 'JBL Bar 5.1 Soundbar', 'brand' => 'JBL', 'category' => 'Audio & Headphones', 'cost' => 6500000],
            ['name' => 'Sony SRS-XB100 Speaker', 'brand' => 'Sony', 'category' => 'Audio & Headphones', 'cost' => 550000],
            ['name' => 'Logitech G435 Gaming Headset', 'brand' => 'Logitech', 'category' => 'Gaming', 'cost' => 800000],

            // More fashion items
            ['name' => 'Nike Club Fleece Hoodie', 'brand' => 'Nike', 'category' => 'Men\'s Clothing', 'cost' => 650000],
            ['name' => 'Adidas Essentials Hoodie', 'brand' => 'Adidas', 'category' => 'Men\'s Clothing', 'cost' => 600000],
            ['name' => 'H&M Regular Fit Oxford Shirt', 'brand' => 'H&M', 'category' => 'Men\'s Clothing', 'cost' => 250000],
            ['name' => 'Zara Basic Cotton T-Shirt', 'brand' => 'Zara', 'category' => 'Men\'s Clothing', 'cost' => 180000],
            ['name' => 'Uniqlo EZY Ankle Pants', 'brand' => 'Uniqlo', 'category' => 'Men\'s Clothing', 'cost' => 400000],

            // More home items
            ['name' => 'IKEA HEMNES Chest of Drawers', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 3500000],
            ['name' => 'IKEA EKTORP Sofa', 'brand' => 'IKEA', 'category' => 'Furniture', 'cost' => 6000000],
            ['name' => 'Informa TV Stand 150cm', 'brand' => 'Informa', 'category' => 'Furniture', 'cost' => 1800000],
            ['name' => 'ACE Tool Set 108pcs', 'brand' => 'ACE', 'category' => 'Storage', 'cost' => 850000],

            // More food items
            ['name' => 'Nestle Cerelac Bubur 120g x 12', 'brand' => 'Nestle', 'category' => 'Pantry Staples', 'cost' => 180000],
            ['name' => 'Indofood Bimoli Minyak Goreng 2L', 'brand' => 'Indofood', 'category' => 'Pantry Staples', 'cost' => 42000],
            ['name' => 'Nestle Carnation 500g', 'brand' => 'Nestle', 'category' => 'Dairy', 'cost' => 35000],
        ];
    }

    protected function getSupplierIdForBrand(string $brand): ?int
    {
        foreach ($this->suppliers as $supplier) {
            if (in_array($brand, $supplier['brands'])) {
                return $supplier['id'];
            }
        }
        // Return first supplier as fallback
        return $this->suppliers[0]['id'] ?? null;
    }

    protected function getUomForCategory(string $category): string
    {
        $categoryUoms = [
            'Smartphones' => 'UNIT',
            'Laptops & Computers' => 'UNIT',
            'Audio & Headphones' => 'UNIT',
            'Accessories' => 'PCS',
            'Gaming' => 'UNIT',
            'Footwear' => 'PAIR',
            'Men\'s Clothing' => 'PCS',
            'Women\'s Clothing' => 'PCS',
            'Bags & Accessories' => 'PCS',
            'Furniture' => 'UNIT',
            'Kitchen & Dining' => 'SET',
            'Decor' => 'PCS',
            'Bedding' => 'SET',
            'Storage' => 'PCS',
            'Snacks' => 'BOX',
            'Beverages' => 'BOX',
            'Pantry Staples' => 'PCS',
            'Dairy' => 'BOX',
            'Skincare' => 'PCS',
            'Haircare' => 'PCS',
            'Makeup' => 'PCS',
            'Personal Care' => 'PCS',
        ];

        return $categoryUoms[$category] ?? 'PCS';
    }

    protected function generateSku(string $category, string $brand): string
    {
        $catCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $category), 0, 3));
        $brandCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $brand), 0, 3));
        return $catCode . '-' . $brandCode . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    protected function generateBarcode(): string
    {
        return '899' . str_pad(rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    protected function generatePhoneNumber(): string
    {
        $prefixes = ['0812', '0813', '0821', '0822', '0852', '0853', '0857', '0858', '0878', '0877'];
        return $prefixes[array_rand($prefixes)] . rand(10000000, 99999999);
    }

    protected function generateCompanyName(): string
    {
        $prefixes = ['PT', 'CV', 'UD', 'Toko', 'TB'];
        $names = [
            'Maju Jaya', 'Sentosa Abadi', 'Berkah Makmur', 'Karya Indah', 'Sejahtera',
            'Mandiri Utama', 'Sukses Bersama', 'Prima Jaya', 'Global Teknik', 'Multi Guna',
            'Sumber Rejeki', 'Cahaya Baru', 'Mega Pratama', 'Jaya Abadi', 'Bintang Timur',
            'Harapan Baru', 'Gemilang', 'Surya Kencana', 'Mulia Sentosa', 'Anugerah',
        ];
        $suffixes = ['', 'Indonesia', 'Nusantara', 'Sejahtera', 'Mandiri', 'Group'];

        return $prefixes[array_rand($prefixes)] . ' ' .
            $names[array_rand($names)] . ' ' .
            $suffixes[array_rand($suffixes)];
    }

    protected function getStateForCity(string $city): string
    {
        $cityStates = [
            'Jakarta' => 'DKI Jakarta',
            'Surabaya' => 'Jawa Timur',
            'Bandung' => 'Jawa Barat',
            'Medan' => 'Sumatera Utara',
            'Semarang' => 'Jawa Tengah',
            'Makassar' => 'Sulawesi Selatan',
            'Palembang' => 'Sumatera Selatan',
            'Tangerang' => 'Banten',
            'Denpasar' => 'Bali',
            'Yogyakarta' => 'DI Yogyakarta',
            'Batam' => 'Kepulauan Riau',
            'Balikpapan' => 'Kalimantan Timur',
            'Karawang' => 'Jawa Barat',
        ];

        return $cityStates[$city] ?? 'Indonesia';
    }

    protected function weightedRandom(array $items): array
    {
        $totalWeight = array_sum(array_column($items, 'weight'));
        $random = rand(1, $totalWeight);

        $cumulative = 0;
        foreach ($items as $item) {
            $cumulative += $item['weight'];
            if ($random <= $cumulative) {
                return $item;
            }
        }

        return $items[0];
    }

    protected function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== SEEDING SUMMARY ===');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Brands', Brand::count()],
                ['Categories', ProductCategory::count()],
                ['Suppliers', Customer::where('is_customer', false)->count()],
                ['Customers', Customer::where('is_customer', true)->count()],
                ['Products', Product::count()],
                ['Purchase Orders', PurchaseOrder::count()],
                ['Purchase Order Items', PurchaseOrderItem::count()],
                ['Purchase Receivings', PurchaseReceiving::count()],
                ['Purchase Returns', PurchaseReturn::count()],
                ['Sales Orders', SalesOrder::count()],
                ['Sales Order Items', SalesOrderItem::count()],
                ['Sales Shipments', SalesShipment::count()],
                ['Sales Returns', SalesReturn::count()],
                ['Payments', Payment::count()],
                ['Inventory Movements', InventoryMovement::count()],
            ]
        );
    }
}
