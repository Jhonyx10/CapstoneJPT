<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerType;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function create($data)
    {
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
}