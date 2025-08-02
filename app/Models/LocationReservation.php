<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationReservation extends Model
{
    protected $fillable = ['product_id', 'level', 'height'];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
