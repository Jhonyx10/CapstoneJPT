<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJobServiceItem extends Model
{
    protected $fillable = ['repair_job_service_id', 'inventory_id', 'unit_price'];

    public function repairJobService()
    {
        return $this->belongsTo(RepairJobService::class, 'repair_job_service_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
