<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJobServiceWorker extends Model
{
    // 1. Explicitly define the table name since it uses a 3-way naming structure
    protected $table = 'repair_job_service_workers';

    // 2. Protect against mass-assignment vulnerabilities
    protected $fillable = [
        'repair_job_service_id',
        'worker_id',
        'assigned_at'
    ];

    // 3. Cast dates automatically to Carbon instances
    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the specific job-service item this assignment belongs to.
     */
    public function repairJobService()
    {
        // Points back to the intermediate pivot row
        return $this->belongsTo(RepairJobService::class, 'repair_job_service_id');
    }

    /**
     * Get the worker (User model with a mechanic/painter role) assigned to this task.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}