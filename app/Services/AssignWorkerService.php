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

                // 1. Create assignments for each worker selected for this service
                foreach ($workerIds as $workerId) {
                    $assignments[] = RepairJobServiceWorker::firstOrCreate([
                        'repair_job_service_id' => $repairJobServiceId,
                        'worker_id' => $workerId,
                    ], [
                        'assigned_at' => now(),
                    ]);
                }
                // 2. Update the status of this specific service item to 'assigned'
                // (Assuming you have a RepairJobService pivot model or table)
                DB::table('repair_job_services') // Replace with your actual pivot table name or RepairJobService::find()
                    ->where('id', $repairJobServiceId)
                    ->update([
                        'status' => 'assigned' // Or your specific Enum value if applicable
                    ]);
            }

            // 3. Update the overall repair job status to assigned
            // Safeguard: make sure the index exists before querying
            $repairJobId = $data[0]['repair_job_id'] ?? null;
            
            if ($repairJobId) {
                $repairJob = RepairJob::with('vehicle.user')->findOrFail($repairJobId);
                $repairJob->status = RepairJobStatus::Assigned;
                $repairJob->save();

                event(new \App\Events\RepairJobStatusUpdated($repairJob));

                $customer = $repairJob->vehicle?->user;
                if ($customer) {
                    $vehicleLabel = trim(
                        ($repairJob->vehicle->brand ?? '') . ' ' . ($repairJob->vehicle->model ?? '')
                    );

                    event(new \App\Events\RepairJobAssigned(
                        userId: $customer->id,
                        repairJobId: $repairJob->id,
                        vehicleLabel: $vehicleLabel,
                    ));
                }
            }

            return $assignments;
        });
    }

    public function getWorkers()
    {
        return User::where('role','worker')->with('workerType')->get();
    }
}