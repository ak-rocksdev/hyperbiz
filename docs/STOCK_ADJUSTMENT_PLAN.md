# Stock Adjustment / Stock Opname - Implementation Plan

## 1. Overview & Business Context

### Apa itu Stock Adjustment?

Stock Adjustment adalah fitur untuk menyesuaikan kuantitas stock yang tercatat di sistem dengan kondisi aktual di gudang. Ini bisa terjadi karena beberapa alasan:

| Skenario | Tipe | Contoh |
|----------|------|--------|
| **Stock Opname** | Add/Deduct | Hasil hitung fisik berbeda dari sistem |
| **Barang Rusak** | Deduct | Produk rusak/expired harus dibuang |
| **Barang Hilang** | Deduct | Kehilangan, pencurian |
| **Ditemukan Barang** | Add | Barang ditemukan yang tidak tercatat |
| **Sample/Giveaway** | Deduct | Barang keluar untuk sample/hadiah |
| **Retur Internal** | Add | Barang dikembalikan dari departemen lain |
| **Koreksi Kesalahan** | Add/Deduct | Salah input sebelumnya |

### Pain Points Tanpa Fitur Ini

1. **Data tidak akurat** - Stock di sistem tidak sesuai realita
2. **Tidak ada audit trail** - Tidak tahu siapa yang mengubah dan alasannya
3. **Sulit tracking kerugian** - Tidak bisa menganalisis pola kehilangan
4. **Stock opname manual** - Harus input satu-satu tanpa workflow terstruktur
5. **Tidak ada approval** - Perubahan besar tanpa persetujuan supervisor

---

## 2. Current Implementation Analysis

### Yang Sudah Ada

```
✅ Adjust Stock Modal (di Inventory Detail)
   - Path: /inventory/product/{id}
   - Tipe: Add / Deduct
   - Input: Quantity, Unit Cost (untuk Add), Reason
   - Backend: InventoryController::adjust()
   - Creates: inventory_movements record

❌ Adjust Stock Link (di Product Detail) → BROKEN
   - Path: /inventory/adjustments/create?product_id={id}
   - Route belum dibuat
```

### Limitasi Current Implementation

| Aspek | Status | Masalah |
|-------|--------|---------|
| Single item adjustment | ✅ Ada | - |
| Batch/bulk adjustment | ❌ Tidak ada | Tidak bisa adjust banyak item sekaligus |
| Predefined reasons | ❌ Tidak ada | User harus ketik manual setiap kali |
| Approval workflow | ❌ Tidak ada | Siapapun bisa adjust tanpa approval |
| Stock Opname workflow | ❌ Tidak ada | Tidak ada fitur count sheet |
| Adjustment report | ❌ Tidak ada | Tidak bisa lihat summary adjustments |
| Permission control | ⚠️ Partial | Tidak ada permission khusus adjust |

---

## 3. Proposed Implementation

### Level 1: Quick Fix (Basic)
> *Memperbaiki link yang rusak dan membuat halaman adjustment terpisah*

**Scope:**
- Buat halaman `/inventory/adjustments/create`
- Support single product adjustment dengan form yang lebih lengkap
- Predefined adjustment reasons (dropdown)

**Effort:** 1-2 jam

---

### Level 2: Standard Implementation (Recommended)
> *Fitur adjustment yang lengkap untuk operasional sehari-hari*

**Scope:**
- Halaman list adjustments history
- Halaman create single adjustment
- Halaman bulk adjustment (multiple products)
- Predefined adjustment reasons dengan kategori
- Permission-based access control

**Effort:** 4-6 jam

---

### Level 3: Enterprise Implementation
> *Full stock opname workflow dengan approval*

**Scope:**
- Semua fitur Level 2
- Stock Count Sheet (untuk opname)
- Approval workflow untuk adjustment besar
- Adjustment report & analytics
- Scheduler untuk stock opname rutin

**Effort:** 8-12 jam

---

## 4. Detailed Plan - Level 2 (Recommended)

### 4.1 Database Changes

#### New Table: `adjustment_reasons` (Optional - bisa juga hardcode)

```sql
CREATE TABLE adjustment_reasons (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    type ENUM('add', 'deduct', 'both') DEFAULT 'both',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Sample data
INSERT INTO adjustment_reasons (code, name, type) VALUES
('STOCK_OPNAME', 'Stock Opname / Physical Count', 'both'),
('DAMAGED', 'Damaged / Broken', 'deduct'),
('EXPIRED', 'Expired', 'deduct'),
('LOST', 'Lost / Missing', 'deduct'),
('THEFT', 'Theft / Stolen', 'deduct'),
('SAMPLE', 'Sample / Giveaway', 'deduct'),
('FOUND', 'Found Item', 'add'),
('RETURN_INTERNAL', 'Internal Return', 'add'),
('DATA_CORRECTION', 'Data Entry Correction', 'both'),
('OTHER', 'Other (specify in notes)', 'both');
```

**Alternatif**: Tidak perlu tabel baru, cukup hardcode di config atau enum.

#### Modify: `inventory_movements` (Tidak perlu perubahan)

Tabel `inventory_movements` sudah cukup untuk menyimpan adjustment records dengan `movement_type` = `adjustment_in` atau `adjustment_out`.

### 4.2 Backend Changes

#### New Routes

```php
// routes/web.php
Route::prefix('inventory')->group(function () {
    // ... existing routes ...

    // Stock Adjustments
    Route::get('/adjustments',                      [InventoryController::class, 'adjustmentList'])->name('inventory.adjustments');
    Route::get('/adjustments/create',               [InventoryController::class, 'createAdjustment'])->name('inventory.adjustments.create');
    Route::post('/api/adjustments/store',           [InventoryController::class, 'storeAdjustment']);
    Route::post('/api/adjustments/bulk-store',      [InventoryController::class, 'storeBulkAdjustment']);
});
```

#### New Controller Methods

```php
// InventoryController.php

/**
 * List all adjustments with filters.
 */
public function adjustmentList(Request $request)
{
    // Filter by date range, product, adjustment type, reason
    // Return paginated list of adjustment movements
}

/**
 * Show create adjustment form.
 */
public function createAdjustment(Request $request)
{
    $productId = $request->query('product_id');
    $product = $productId ? Product::with('inventoryStock')->find($productId) : null;

    $products = Product::where('is_active', true)
        ->with(['uom', 'inventoryStock'])
        ->get();

    $reasons = $this->getAdjustmentReasons();

    return Inertia::render('Inventory/Adjustments/Create', [
        'selectedProduct' => $product,
        'products' => $products,
        'reasons' => $reasons,
    ]);
}

/**
 * Store single adjustment.
 */
public function storeAdjustment(Request $request)
{
    $validated = $request->validate([
        'product_id' => 'required|exists:mst_products,id',
        'adjustment_type' => 'required|in:add,deduct',
        'quantity' => 'required|numeric|min:0.001',
        'unit_cost' => 'nullable|numeric|min:0',
        'reason_code' => 'required|string',
        'notes' => 'nullable|string|max:500',
    ]);

    // Process adjustment...
}

/**
 * Store bulk adjustments (multiple products).
 */
public function storeBulkAdjustment(Request $request)
{
    $validated = $request->validate([
        'adjustments' => 'required|array|min:1',
        'adjustments.*.product_id' => 'required|exists:mst_products,id',
        'adjustments.*.adjustment_type' => 'required|in:add,deduct',
        'adjustments.*.quantity' => 'required|numeric|min:0.001',
        'adjustments.*.unit_cost' => 'nullable|numeric|min:0',
        'adjustments.*.reason_code' => 'required|string',
        'adjustments.*.notes' => 'nullable|string|max:500',
    ]);

    // Process in transaction...
}

/**
 * Get predefined adjustment reasons.
 */
private function getAdjustmentReasons(): array
{
    return [
        ['code' => 'STOCK_OPNAME', 'name' => 'Stock Opname / Physical Count', 'type' => 'both'],
        ['code' => 'DAMAGED', 'name' => 'Damaged / Broken', 'type' => 'deduct'],
        ['code' => 'EXPIRED', 'name' => 'Expired', 'type' => 'deduct'],
        ['code' => 'LOST', 'name' => 'Lost / Missing', 'type' => 'deduct'],
        ['code' => 'THEFT', 'name' => 'Theft / Stolen', 'type' => 'deduct'],
        ['code' => 'SAMPLE', 'name' => 'Sample / Giveaway', 'type' => 'deduct'],
        ['code' => 'FOUND', 'name' => 'Found Item', 'type' => 'add'],
        ['code' => 'RETURN_INTERNAL', 'name' => 'Internal Return', 'type' => 'add'],
        ['code' => 'DATA_CORRECTION', 'name' => 'Data Entry Correction', 'type' => 'both'],
        ['code' => 'OTHER', 'name' => 'Other (specify in notes)', 'type' => 'both'],
    ];
}
```

### 4.3 Frontend Pages

#### Page 1: Adjustment List (`/inventory/adjustments`)

```
┌─────────────────────────────────────────────────────────────────┐
│ Stock Adjustments                        [+ Create Adjustment]  │
├─────────────────────────────────────────────────────────────────┤
│ Filters: [Date Range] [Product ▼] [Type ▼] [Reason ▼] [Search] │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌─────────┬──────────┬────────┬────────┬────────┬─────────────┐│
│ │ Date    │ Product  │ Type   │ Qty    │ Reason │ Notes       ││
│ ├─────────┼──────────┼────────┼────────┼────────┼─────────────┤│
│ │ 30 Jan  │ Widget A │ ▼ -50  │ -50    │ Damaged│ Rusak...    ││
│ │ 29 Jan  │ Widget B │ ▲ +10  │ +10    │ Opname │ Selisih...  ││
│ │ 28 Jan  │ Gadget C │ ▼ -5   │ -5     │ Sample │ Marketing   ││
│ └─────────┴──────────┴────────┴────────┴────────┴─────────────┘│
│                                                                 │
│ Showing 1-20 of 156                           [< 1 2 3 4 5 >]   │
└─────────────────────────────────────────────────────────────────┘
```

#### Page 2: Create Adjustment (`/inventory/adjustments/create`)

```
┌─────────────────────────────────────────────────────────────────┐
│ ← Back    Create Stock Adjustment                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Adjustment Type                                          │   │
│  │  ○ Single Product    ● Bulk Adjustment                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Products to Adjust                                       │   │
│  │ ┌────────────┬─────────┬────────┬────────┬────────────┐ │   │
│  │ │ Product    │ Current │ Type   │ Qty    │ Reason     │ │   │
│  │ ├────────────┼─────────┼────────┼────────┼────────────┤ │   │
│  │ │ [Select ▼] │ 150     │ [Add▼] │ [   ]  │ [Select ▼] │ │   │
│  │ │ Widget A   │ 50      │ Deduct │ 5      │ Damaged    │ │   │
│  │ └────────────┴─────────┴────────┴────────┴────────────┘ │   │
│  │                                        [+ Add Product]   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Notes (Optional)                                         │   │
│  │ ┌───────────────────────────────────────────────────────┐│   │
│  │ │ Stock opname tanggal 30 Januari 2026...               ││   │
│  │ └───────────────────────────────────────────────────────┘│   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│                              [Cancel]  [Submit Adjustment]      │
└─────────────────────────────────────────────────────────────────┘
```

### 4.4 File Structure

```
resources/js/Pages/Inventory/
├── List.vue                    # existing
├── Detail.vue                  # existing (has modal adjustment)
├── Movements.vue               # existing
├── Adjustments/
│   ├── Index.vue               # NEW - list adjustments
│   └── Create.vue              # NEW - create adjustment form
```

### 4.5 Permissions

```php
// Add new permissions
'inventory.adjustments.view'    // View adjustment history
'inventory.adjustments.create'  // Create adjustments
'inventory.adjustments.bulk'    // Bulk adjustments (optional)
```

---

## 5. Technical Considerations

### 5.1 Average Cost Impact

Saat adjustment **ADD**:
- User harus input `unit_cost`
- Jika tidak diisi, gunakan `average_cost` saat ini
- Average cost akan di-recalculate

Saat adjustment **DEDUCT**:
- Unit cost otomatis diambil dari `average_cost` saat ini
- Average cost tidak berubah

### 5.2 Negative Stock Prevention

```php
if ($type === 'deduct' && $quantity > $stock->quantity_available) {
    throw new \Exception("Cannot deduct {$quantity}. Only {$stock->quantity_available} available.");
}
```

### 5.3 Audit Trail

Setiap adjustment tercatat di:
- `inventory_movements` table
- `system_logs` table (via LogsSystemChanges trait)

### 5.4 Movement Type Mapping

```php
// Reason code to movement type
$movementType = $adjustmentType === 'add'
    ? InventoryMovement::TYPE_ADJUSTMENT_IN
    : InventoryMovement::TYPE_ADJUSTMENT_OUT;

// Store reason_code in notes or add new column
$notes = "[{$reasonCode}] {$userNotes}";
```

---

## 6. Mockup UI Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                      USER JOURNEY                               │
└─────────────────────────────────────────────────────────────────┘

[Product Detail] ──────────────────────────────────────────────────
     │
     │ Click "Adjust Stock"
     ▼
[Adjustment Create Page] ──────────────────────────────────────────
     │
     │ Select product(s), type, quantity, reason
     │
     │ Click "Submit"
     ▼
[Confirmation Dialog] ─────────────────────────────────────────────
     │
     │ "Adjust 5 units of Widget A (Damaged)?"
     │
     │ Click "Confirm"
     ▼
[Success] ─────────────────────────────────────────────────────────
     │
     │ Redirect to Adjustment List with success message
     ▼
[Adjustment List] ─────────────────────────────────────────────────
```

---

## 7. Recommended Implementation Order

1. **Fix broken link** - Redirect `/inventory/adjustments/create` ke `/inventory/product/{id}` untuk sementara (5 menit)

2. **Create Adjustment List page** - Display filtered inventory movements with adjustment types (1 jam)

3. **Create Single Adjustment page** - With product selector and reason dropdown (2 jam)

4. **Add Bulk Adjustment** - Add multiple products in one form (2 jam)

5. **Add permissions** - Control access to adjustment features (30 menit)

---

## 8. Questions for Confirmation

Sebelum implementasi, mohon konfirmasi:

### Q1: Level implementasi mana yang diinginkan?
- [ ] Level 1 - Quick Fix (perbaiki link + form sederhana)
- [ ] Level 2 - Standard (list + single + bulk adjustment)
- [ ] Level 3 - Enterprise (+ stock opname + approval)

### Q2: Apakah perlu tabel `adjustment_reasons` terpisah?
- [ ] Ya, buat tabel baru (bisa di-manage admin)
- [ ] Tidak, hardcode saja di config/enum

### Q3: Apakah perlu approval workflow?
- [ ] Ya, adjustment > X unit perlu approval
- [ ] Tidak, siapapun dengan permission bisa langsung adjust

### Q4: Apakah perlu fitur Stock Opname (count sheet)?
- [ ] Ya, perlu fitur khusus untuk opname
- [ ] Tidak, cukup bulk adjustment saja

### Q5: Sidebar menu placement?
- [ ] Submenu di bawah "Inventory"
- [ ] Terpisah sebagai menu sendiri

---

*Document created: 2026-01-30*
*Status: Awaiting user confirmation*
