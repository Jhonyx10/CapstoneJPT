<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RepairJob extends Model
{
    protected $table='repair_jobs';

    protected $fillable = [
        'vehicle_id','customer_information_id', 'status', 'total_estimated_cost', 'start_date', 'end_date', 'reference_number'    
    ];

    protected static function booted()
    {
        // Automatically runs right before a new RepairJob is saved to the database
        static::creating(function ($repairJob) {
            do {
                // Generates an uppercase tracking code like: JAP-X87B2K9A
                $code = 'JAP-' . strtoupper(Str::random(8));
            } while (self::where('reference_number', $code)->exists()); // Double check uniqueness

            $repairJob->reference_number = $code;
        });
    }
    
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

    public function repairJobServices()
    {
        return $this->hasMany(RepairJobService::class, 'repair_job_id');
    }

    // A job has a historical timeline log tracking status shifts
    public function logs()
    {
        return $this->hasMany(RepairJobLog::class, 'repair_id');
    }

    // A job can only generate one single bill/invoice
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'repair_job_id');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class, 'repair_id');
    }

    public function customerInformation()
    {
        return $this->belongsTo(CustomerInformation::class, 'customer_information_id');
    }
}
