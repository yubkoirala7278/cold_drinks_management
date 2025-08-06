<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarcodeTracker extends Model
{
    protected $fillable=['prefix','last_number'];
}
