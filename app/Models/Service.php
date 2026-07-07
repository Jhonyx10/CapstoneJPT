<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'name', 'worker_type', 'base_price', 'item_category_id'
    ];

    public function requiredWorkerType()
    {
        return $this->belongsTo(WorkerType::class, 'worker_type');
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function workers()
    {
        return $this->belongsToMany(User::class, 'repair_job_service_workers', 'repair_job_service_id', 'worker_id');
    }

    // A service can be linked to multiple ongoing repair jobs across the shop
    public function repairJobs()
    {
        return $this->belongsToMany(RepairJob::class, 'repair_job_services')
                    ->using(RepairJobService::class)
                    ->withPivot('id', 'worker_id', 'status', 'actual_price')
                    ->withTimestamps();
    }
}
