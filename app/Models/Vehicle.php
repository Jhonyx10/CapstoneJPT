<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table ='vehicles';

    protected $fillable = [
        'user_id', 'brand', 'model', 'body_type', 'engine_type', 'transmission',
        'chassis_number', 'plate_number', 'image', 'status', 'price',
    ];

    protected $casts = [
        'status' => VehicleStatus::class,
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

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
