<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'worker_type_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function workerType()
    {
        return $this->belongsTo(WorkerType::class);
    }

    // If user is a customer, they can own multiple vehicles
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'customer_id');
    }

    // A worker can be assigned to multiple service line items across jobs
    public function assignedServiceLines()
    {
        return $this->belongsToMany(RepairJobService::class, 'repair_job_service_workers', 'worker_id', 'repair_job_service_id')
                    ->withPivot('id', 'assigned_at')
                    ->withTimestamps();
    }
}
