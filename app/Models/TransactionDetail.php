<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class TransactionDetail extends Model
{
    use HasFactory, LogsSystemChanges, BelongsToCompany;

    protected $fillable = [
        'transaction_id',
        'mst_product_id',
        'quantity',
        'price',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'mst_product_id');
    }
}

