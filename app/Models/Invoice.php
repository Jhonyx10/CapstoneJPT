<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'repair_job_id',
        'booking_id',
        'type',
        'parent_id',
        'version',
        'invoice_number',
        'labor_cost',
        'material_cost',
        'tax',
        'total_amount',
        'amount_due',
        'status',
        'authorized_at',
        'rejection_reason',
        'notes',
    ];
    
    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    // An invoice can have multiple payment attempts (e.g., partial payments or retries)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function booking()
{
    return $this->belongsTo(Booking::class);
}
}
