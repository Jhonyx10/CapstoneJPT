<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table ='vehicles';

    protected $fillable = [
        'user_id', 'brand', 'model', 'body_type', 'engine_type', 'transmission',
        'chassis_number', 'plate_number', 'status'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // A vehicle can undergo multiple repair jobs over its lifetime
    public function repairJobs()
    {
        return $this->hasMany(RepairJob::class);
    }
}
