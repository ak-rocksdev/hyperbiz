# Legacy Tables Cleanup Documentation

## Executive Summary

Sistem HyperBiz memiliki **2 sistem transaksi paralel**:
1. **Sistem Lama (Legacy)**: `transactions`, `transaction_details`, `transactions_log`
2. **Sistem Baru**: `sales_orders`, `purchase_orders`, `inventory_movements`, dll.

Berdasarkan pengecekan menyeluruh, **sistem lama TIDAK LAGI AKTIF digunakan dalam operasional sehari-hari**, namun kode dan route-nya masih ada. Dokumen ini berisi panduan untuk membersihkan kode legacy tersebut.

---

## Status Penggunaan Legacy Tables

### 1. Tabel `transactions`

| Aspek | Status | Detail |
|-------|--------|--------|
| Route | ✅ Masih terdaftar | `/transaction/*` di `routes/web.php:115-126` |
| Menu Sidebar | ❌ **TIDAK ADA** | Tidak ada link di `Sidebar.vue` |
| Controller | ✅ Masih ada | `TransactionController.php` |
| Vue Pages | ✅ Masih ada | `Transaction/List.vue`, `Create.vue`, `Edit.vue`, `Pdf.vue` |
| Dashboard | ⚠️ Hanya count | `Transaction::count()` untuk statistik |

**Kesimpulan**: Route dan controller ada, tapi **tidak dapat diakses dari UI** karena tidak ada menu link.

### 2. Tabel `transaction_details`

| Aspek | Status | Detail |
|-------|--------|--------|
| Model | ✅ Masih ada | `TransactionDetail.php` |
| Digunakan di | `TransactionController.php` saja | Untuk CRUD transaksi legacy |

### 3. Tabel `transactions_log`

| Aspek | Status | Detail |
|-------|--------|--------|
| Model | ✅ Masih ada | `TransactionLog.php` |
| Digunakan di | `TransactionController.php` saja | Untuk logging transaksi legacy |

---

## Daftar File yang Perlu Dihapus/Dibersihkan

### A. Files to DELETE (Hapus Sepenuhnya)

#### Controllers
```
app/Http/Controllers/TransactionController.php
```

#### Models
```
app/Models/Transaction.php
app/Models/TransactionDetail.php
app/Models/TransactionLog.php
```

#### Enums
```
app/Enums/TransactionStatus.php
```

#### Vue Pages
```
resources/js/Pages/Transaction/List.vue
resources/js/Pages/Transaction/Create.vue
resources/js/Pages/Transaction/Edit.vue
resources/js/Pages/Transaction/Pdf.vue
```

#### Console Commands (Opsional - bisa disimpan untuk referensi)
```
app/Console/Commands/MigrateLegacyTransactions.php
```

### B. Files to MODIFY (Perlu Diedit)

#### 1. `routes/web.php`

**Hapus baris 115-126:**
```php
// DELETE THIS BLOCK
Route::prefix('transaction')->group(function () {
    Route::get('/list',                     [TransactionController::class, 'list'])->name('transaction.list');
    Route::get('/create',                   [TransactionController::class, 'create'])->name('transaction.create');
    Route::post('/api/store',               [TransactionController::class, 'store']);
    Route::get('/api/detail/{id}',          [TransactionController::class, 'detailApi']);
    Route::get('/edit/{id}',                [TransactionController::class, 'edit'])->name('transaction.edit');
    Route::put('/api/update/{id}',          [TransactionController::class, 'update']);
    Route::delete('/api/delete/{id}',       [TransactionController::class, 'delete'])->name('transaction.delete');
    Route::get('/{id}/export-pdf',          [TransactionController::class, 'exportPdf'])->name('transactions.export-pdf');
    Route::get('/{id}/preview',             [TransactionController::class, 'previewPdf']);
});
```

**Hapus import di baris 11:**
```php
// DELETE THIS LINE
use App\Http\Controllers\TransactionController;
```

#### 2. `app/Http/Controllers/DashboardController.php`

**Hapus baris 7:**
```php
// DELETE THIS LINE
use App\Models\Transaction;
```

**Ubah baris 39 dari:**
```php
'total_transactions' => Transaction::count(),
```

**Menjadi (opsi A - hapus sepenuhnya):**
```php
// Hapus baris ini, atau
```

**Atau (opsi B - ganti dengan total SO + PO):**
```php
'total_transactions' => SalesOrder::count() + PurchaseOrder::count(),
```

#### 3. `app/Models/Customer.php`

**Hapus relasi legacy (baris 77-97):**
```php
// DELETE THIS BLOCK
/**
 * Get transaction total value where 'transaction_type' is purchase or sell
 */
public function getTotalSell()
{
    return $this->hasMany(Transaction::class, 'mst_client_id')
        ->where('transaction_type', 'sell');
}

public function getTotalPurchase()
{
    return $this->hasMany(Transaction::class, 'mst_client_id')
        ->where('transaction_type', 'purchase');
}

/**
 * Relationship with Transaction
 */
public function transactions()
{
    return $this->hasMany(Transaction::class, 'mst_client_id');
}
```

#### 4. `resources/js/Pages/Dashboard.vue`

**Hapus atau ganti bagian "Transactions" card (baris 610-623):**

Opsi A - Hapus sepenuhnya:
```vue
<!-- DELETE THIS BLOCK -->
<!-- Transactions -->
<div class="card">
    <div class="card-body p-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                <i class="ki-filled ki-dollar text-success text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-gray-900">{{ stats?.total_transactions || 0 }}</div>
                <div class="text-xs text-gray-500 truncate">Transactions</div>
            </div>
        </div>
    </div>
</div>
```

Opsi B - Ganti dengan "Total Orders":
```vue
<!-- Total Orders -->
<div class="card">
    <div class="card-body p-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                <i class="ki-filled ki-dollar text-success text-lg"></i>
            </div>
            <div class="min-w-0">
                <div class="text-xl font-bold text-gray-900">{{ stats?.total_transactions || 0 }}</div>
                <div class="text-xs text-gray-500 truncate">Total Orders</div>
            </div>
        </div>
    </div>
</div>
```

#### 5. `app/Console/Commands/SeedDemoData.php`

**Hapus referensi tabel legacy dari `$truncateOrder` array (baris 76-78):**
```php
// DELETE THESE LINES (or keep for safety during transition)
'transactions_log',
'transaction_details',
'transactions',
```

---

## Database Tables to DROP

Setelah semua kode dibersihkan, jalankan migration untuk menghapus tabel:

```sql
-- Backup dulu jika perlu
-- mysqldump -u root hyperbiz transactions transaction_details transactions_log > legacy_backup.sql

-- Drop tables
DROP TABLE IF EXISTS transactions_log;
DROP TABLE IF EXISTS transaction_details;
DROP TABLE IF EXISTS transactions;
```

Atau buat migration file:

```php
// database/migrations/xxxx_xx_xx_drop_legacy_transaction_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('transactions_log');
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
    }

    public function down(): void
    {
        // Tidak perlu restore - tabel legacy
    }
};
```

---

## Migration Files yang Bisa Dihapus (Opsional)

File-file ini bisa dihapus setelah tabel di-drop, tapi biasanya dibiarkan untuk history:

```
database/migrations/2024_12_27_231306_create_transaction_details_table.php
database/migrations/2024_12_28_215223_create_transactions_log_table.php
```

---

## Checklist Cleanup

- [ ] Backup database terlebih dahulu
- [ ] Hapus `TransactionController.php`
- [ ] Hapus `Transaction.php`, `TransactionDetail.php`, `TransactionLog.php`
- [ ] Hapus `TransactionStatus.php`
- [ ] Hapus Vue pages di `resources/js/Pages/Transaction/`
- [ ] Edit `routes/web.php` - hapus routes transaction
- [ ] Edit `DashboardController.php` - hapus/ganti Transaction::count()
- [ ] Edit `Customer.php` - hapus relasi transactions
- [ ] Edit `Dashboard.vue` - hapus/ganti transactions card
- [ ] Edit `SeedDemoData.php` - hapus tabel legacy dari truncateOrder (opsional)
- [ ] Buat migration untuk drop tables
- [ ] Jalankan `php artisan migrate`
- [ ] Test semua fitur untuk memastikan tidak ada error
- [ ] Hapus file migration lama (opsional)
- [ ] Hapus `MigrateLegacyTransactions.php` (opsional - bisa disimpan untuk referensi)

---

## Diagram Perbandingan Sistem

```
SISTEM LAMA (Legacy)                    SISTEM BARU (Current)
════════════════════                    ════════════════════

transactions                            sales_orders
  └── transaction_details               purchase_orders
                                          └── sales_order_items
transactions_log                          └── purchase_order_items

                                        inventory_movements
                                        inventory_stock
                                        payments
                                        purchase_receivings
                                        sales_shipments
                                        sales_returns
                                        purchase_returns
```

---

## Notes

1. **Sistem baru sudah lengkap** - Semua fitur transaksi sudah tersedia di Sales Order, Purchase Order, dan Inventory Management.

2. **Legacy tidak terpakai** - Tidak ada menu sidebar yang mengarah ke `/transaction/*`, sehingga user tidak bisa mengakses fitur legacy dari UI.

3. **Safe to remove** - Karena tabel legacy kosong dan tidak ada yang mengaksesnya, aman untuk dihapus.

4. **Dashboard stats** - Setelah cleanup, ganti `total_transactions` dengan `SalesOrder::count() + PurchaseOrder::count()` atau hapus sepenuhnya.

---

*Document generated: 2026-01-30*
*Last reviewed: Pending user verification*
