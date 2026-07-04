<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJob extends Model
{
    protected $table='repair_jobs';

    protected $fillable = [
        'vehicle_id', 'status', 'total_estimated_cost', 'start_date', 'end_date'
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // A job can include multiple menu services selected by the customer (Many-to-Many via custom Pivot)
    public function services()
    {
        return $this->belongsToMany(Service::class, 'repair_job_services')
                    ->using(RepairJobService::class)
                    ->withPivot('id', 'status', 'actual_price')
                    ->withTimestamps();
    }

    // A job has a historical timeline log tracking status shifts
    public function logs()
    {
        return $this->hasMany(RepairJobLog::class);
    }

    // A job can only generate one single bill/invoice
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'repair_job_id');
    }
}
