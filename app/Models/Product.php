<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'volume_ml'];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function reservedLocations()
    {
        return $this->hasMany(LocationReservation::class);
    }
}
