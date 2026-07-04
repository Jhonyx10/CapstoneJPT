<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_id', 
        'vehicle_id', 
        'status', 
        'government_id_path', 
        'reservation_fee', 
        'expires_at'
    ];

    // The customer who made the reservation
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // The vehicle being held
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // The invoice generated for this downpayment
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}