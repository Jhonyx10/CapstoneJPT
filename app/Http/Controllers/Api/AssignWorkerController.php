<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AssignWorkerService;
use App\Http\Requests\AssignWorkerRequest;

class AssignWorkerController extends Controller
{
    protected $assignWorkerService;

    public function __construct(AssignWorkerService $assignWorkerService)
    {
        $this->assignWorkerService = $assignWorkerService;
    }

    public function store(AssignWorkerRequest $request)
    {
        $assignWorker = $this->assignWorkerService->assignWorker($request->validated());
        return response()->json($assignWorker, 201);
    }

    public function getWorkers()
    {
        $workers = $this->assignWorkerService->getWorkers();
        return response()->json($workers);
    }
}   
