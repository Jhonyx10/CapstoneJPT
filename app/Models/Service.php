<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'name', 'worker_type', 'base_price'
    ];

    public function requiredRole()
    {
        return $this->belongsTo(Role::class, 'worker_type');
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
