<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = ['batch_id', 'barcode'];
    
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
    
    public function inventory()
    {
        return $this->hasOne(Inventory::class)->whereNull('removed_at');
    }
}
