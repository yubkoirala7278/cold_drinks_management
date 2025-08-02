<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['product_id', 'batch_number', 'production_date', 'expiry_date'];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
