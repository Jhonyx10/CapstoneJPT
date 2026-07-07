<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'repair_id', 'rating', 'comment'
    ];

    public function repair()
    {
        return $this->belongsTo(RepairJob::class, 'repair_id');
    }
}
