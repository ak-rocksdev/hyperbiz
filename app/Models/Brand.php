<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;
use App\Traits\BelongsToCompany;

class Brand extends Model
{
    use LogsSystemChanges, BelongsToCompany;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    protected $table = 'mst_brands';

    protected $fillable = ['name', 'created_by', 'updated_by'];

    public function products()
    {
        return $this->hasMany(Product::class, 'mst_brand_id');
    }
}
