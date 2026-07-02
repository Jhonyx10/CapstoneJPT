<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerType extends Model
{
    protected $table = 'worker_types';

    protected $fillable = [
        'name'
    ];
    
   public function users()
    {
        return $this->hasMany(User::class);
    }

    // A role handles many default services (via worker_type mapping)
    public function services()
    {
        return $this->hasMany(Service::class, 'worker_type');
    }
}
