<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'name'
    ];

    protected static function booted()
    {
        static::deleting(function ($role) {
            // Define the protected system roles
            $protectedRoles = ['admin', 'customer'];

            if (in_array(strtolower($role->name), $protectedRoles)) {
                // Throw an exception to halt the deletion process immediately
                throw new Exception("System integrity violation: The '{$role->name}' role is protected and cannot be deleted.");
            }
        });
    }
    
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
