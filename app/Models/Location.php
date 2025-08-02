<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['level', 'height', 'depth', 'product_id', 'reserved'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class)->whereNull('removed_at');
    }

    public function isFull()
    {
        return $this->inventory()->exists();
    }

    public function isReservedFor(Product $product)
    {
        if ($this->reserved && $this->product_id === $product->id) {
            return true;
        }

        return false;
    }
}
