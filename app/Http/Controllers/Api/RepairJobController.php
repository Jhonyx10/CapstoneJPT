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

    public function getWorkerRepairJobs(Request $request, $id)
    {
        if ((int) $id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($this->repairJobService->getWorkerRepairJobs((int) $id));
    }

    public function getWorkerDashboard(Request $request, $id)
    {
        if ((int) $id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($this->repairJobService->getWorkerDashboard((int) $id));
    }

    public function getRepairJob($id)
    {
        return response()->json($this->repairJobService->getRepairJob($id));
    }

    public function startServiceWork(Request $request, $repairJobId, $repairJobServiceId)
    {
        try {
            $repairJob = $this->repairJobService->startServiceWork(
                $request->user()->id,
                (int) $repairJobId,
                (int) $repairJobServiceId
            );

            return response()->json($repairJob);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function completeServiceWork(Request $request, $repairJobId, $repairJobServiceId)
    {
        try {
            $repairJob = $this->repairJobService->completeServiceWork(
                $request->user()->id,
                (int) $repairJobId,
                (int) $repairJobServiceId
            );

            return response()->json($repairJob);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

}
