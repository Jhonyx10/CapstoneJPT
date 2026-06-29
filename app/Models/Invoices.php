<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'repair_job_id',
        'invoice_number',
        'labor_cost',
        'material_cost',
        'tax',
        'total_amount',
        'amount_due',
        'status',
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
}
