<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $table = 'inventory_logs';

    protected $fillable = [

    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    // (Optional) Links out-logs to the specific vehicle job that consumed the item
    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class)->withDefault();
    }

    // Identifies who executed the inventory update (e.g., item logged out by a painter)
    public function loggedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
