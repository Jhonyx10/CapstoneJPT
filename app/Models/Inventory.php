<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventories';

    protected $fillable = [

    ];

    public function stockLogs()
    {
        return $this->hasMany(InventoryStockLog::class);
    }
}
