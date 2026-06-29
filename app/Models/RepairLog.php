<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairLog extends Model
{
    
    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class);
    }

    // Tracks the exact user (Admin/Foreman) who moved the job to the next stage
    public function operator()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
