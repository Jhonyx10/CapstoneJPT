<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RepairJobService;

class RepairJobController extends Controller
{
    protected $repairJobService;

    public function __construct(RepairJobService $repairJobService) {
        $this->repairJobService = $repairJobService;
    }

    public function index() {
        return response()->json($this->repairJobService->getAll());
    }

    public function getRepairJobs()
    {
        return response()->json($this->repairJobService->getRepairJobs());
    }

    public function getRepairHistory()
    {
        return response()->json($this->repairJobService->getRepairHistory());
    }

    public function getCustomerRepairJobs(Request $request)
    {
        return response()->json($this->repairJobService->getCustomerRepairJobs($request->user()->id));
    }

    public function getRepairJob($id)
    {
        return response()->json($this->repairJobService->getRepairJob($id));
    }

}
