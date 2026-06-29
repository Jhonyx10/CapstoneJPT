<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RepairJobService extends Pivot
{
    protected $table = 'repair_job_services';

    protected $fillable = [
        'repair_job_id',
        'service_id',
        'status',
        'actual_price',
    ];

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class, 'repair_job_id');
    }

    // Connects the pivot instance directly to the specific service meta description
    public function service()
   {
    return $this->belongsToMany(Service::class, 'repair_job_services')
                ->withPivot('id', 'status') // 👈 Tells Laravel these columns exist on the pivot
                ->withTimestamps();
    }

    // Identifies the unique assigned worker performing this checklist line-item
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function assignments()
    {
        return $this->hasMany(RepairJobServiceWorker::class, 'repair_job_service_id');
    }
}
