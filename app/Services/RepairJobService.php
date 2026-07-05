<?php

namespace App\Services;

use App\Models\RepairJob;
use App\Enums\RepairJobStatus;

class RepairJobService
{
    public function getAll() {
        return RepairJob::with('vehicle', 'services')->get();
    }

    public function getRepairJobs() {
        return RepairJob::with('vehicle', 'services.requiredWorkerType', 'services.workers', 'invoice')
                        ->whereNotIn('status', [
                            RepairJobStatus::Pending,
                            RepairJobStatus::Completed,
                            RepairJobStatus::Cancelled,
                        ])
                        ->get();
    }

    public function getRepairHistory()
    {
        return RepairJob::with([
            'vehicle',
            'services.workers',
            'invoice',
            'logs.operator',
        ])
            ->whereIn('status', [
                RepairJobStatus::Completed->value,
                RepairJobStatus::Cancelled->value,
            ])
            ->orderByRaw('COALESCE(end_date, updated_at) DESC')
            ->get();
    }

    public function getCustomerRepairJobs($userId)  
    {
        return RepairJob::with('vehicle')
            ->whereHas('vehicle', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->get();
        }
    
    public function getRepairJob($id)
    {
        return RepairJob::with('vehicle', 'services.requiredWorkerType', 'services.workers', 'invoice')->find($id);
    }

    public function storeRepairJob($data) {
        return RepairJob::create($data);
    }

    public function updateRepairJob($data, $id) {
        $repairJob = RepairJob::find($id);
        $repairJob->update($data);
        return $repairJob;
    }

    public function deleteRepairJob($id) {
        $repairJob = RepairJob::find($id);
        $repairJob->delete();
        return $repairJob;
    }
}