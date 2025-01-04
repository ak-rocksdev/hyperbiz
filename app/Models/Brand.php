<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsSystemChanges;

class Brand extends Model
{
    use LogsSystemChanges;

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
}
