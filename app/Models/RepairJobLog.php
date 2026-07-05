<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairJobLog extends Model
{
    protected $table = 'repair_logs';

    protected $fillable = [
        'repair_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    public function repairJob()
    {
        return $this->belongsTo(RepairJob::class, 'repair_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
