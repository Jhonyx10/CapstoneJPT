<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerType;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create($data)
    {
        if (! isset($data['worker_type_id']) && isset($data['worker_type'])) {
            $data['worker_type_id'] = $data['worker_type'];
        }

        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function getAll()
    {
        return User::where('role','worker')->with('workerType')->get();
    }

    public function getWorkerTypes()
    {
        return WorkerType::all();
    }

    public function createWorkerType($data) {
        return WorkerType::create($data);
    }
}