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

    /** Original invoice this supplemental/add-on bill is linked to */
    public function parent()
    {
        return $this->belongsTo(Invoice::class, 'parent_id');
    }

    /** Additional invoices created for extra work discovered during repair */
    public function children()
    {
        return $this->hasMany(Invoice::class, 'parent_id');
    }
}
