<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInformation extends Model
{
    protected $table = 'customer_information';
    
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
    ];

    public function repairJobs()
    {
        return $this->hasMany(RepairJob::class);
    }
}
