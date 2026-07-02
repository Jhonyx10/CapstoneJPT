<?php

namespace App\Services;

use App\Models\RepairJob;
use App\Models\User;
use App\Models\RepairJobServiceWorker;
use App\Models\RepairJobService;
use App\Enums\RepairJobStatus;
use Illuminate\Support\Facades\DB;

class AssignWorkerService
{
    public function assignWorker($data)
    {
        return DB::transaction(function () use ($data) {
            $assignments = [];

            foreach ($data as $item) {
                $repairJobServiceId = $item['repair_job_service_id'];
                $workerIds = $item['worker_ids'];

                foreach ($workerIds as $workerId) {
                    $assignments[] = RepairJobServiceWorker::firstOrCreate([
                        'repair_job_service_id' => $repairJobServiceId,
                        'worker_id' => $workerId,
                    ], [
                        'assigned_at' => now(),
                    ]);
                }
            }

            // Update repair job status to assigned
            $repairJob = RepairJob::find($data[0]['repair_job_id']);
            $repairJob->status = RepairJobStatus::Assigned;
            $repairJob->save();

            return $assignments;
        });
    }

    public function getWorkers()
    {
        return User::where('role','worker')->with('workerType')->get();
    }
}