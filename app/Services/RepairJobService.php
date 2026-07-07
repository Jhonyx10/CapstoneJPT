<?php

namespace App\Services;

use App\Models\RepairJob;
use App\Models\RepairJobService as RepairJobServiceLine;
use App\Models\RepairJobServiceWorker;
use App\Enums\RepairJobStatus;
use App\Events\RepairJobStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RepairJobService
{
    public function getAll() {
        return RepairJob::with('vehicle', 'services')->get();
    }

    public function getRepairJobs() {
        $jobs = RepairJob::with('vehicle', 'services.requiredWorkerType', 'invoice')
                        ->whereNotIn('status', [
                            RepairJobStatus::Pending,
                            RepairJobStatus::Completed,
                            RepairJobStatus::Cancelled,
                        ])
                        ->get();
        $this->hydrateServiceWorkers($jobs);
        return $jobs;
    }

    public function getRepairHistory()
    {
        $jobs = RepairJob::with([
            'vehicle',
            'services.requiredWorkerType',
            'invoice',
            'logs.operator',
        ])
            ->whereIn('status', [
                RepairJobStatus::Completed->value,
                RepairJobStatus::Cancelled->value,
            ])
            ->orderByRaw('COALESCE(end_date, updated_at) DESC')
            ->get();
        $this->hydrateServiceWorkers($jobs);
        return $jobs;
    }

    public function getCustomerRepairJobs($userId)  
    {
        $jobs = RepairJob::with(['vehicle', 'services', 'invoice', 'rating'])
            ->whereHas('vehicle', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderByDesc('created_at')
            ->get();
        $this->hydrateServiceWorkers($jobs);
        return $jobs;
    }

    public function getWorkerRepairJobs(int $workerId)
    {
        $jobs = RepairJob::with('vehicle.user', 'services.requiredWorkerType', 'invoice')
            ->whereHas('repairJobServices.workers', function ($query) use ($workerId) {
                $query->where('users.id', $workerId);
            })
            ->whereNotIn('status', [
                RepairJobStatus::Completed->value,
                RepairJobStatus::Cancelled->value,
            ])
            ->orderByDesc('updated_at')
            ->get();
        $this->hydrateServiceWorkers($jobs);
        return $jobs;
    }

    public function getWorkerDashboard(int $workerId): array
    {
        $jobs = RepairJob::with(['services'])
            ->whereHas('repairJobServices.workers', function ($query) use ($workerId) {
                $query->where('users.id', $workerId);
            })
            ->where('status', '!=', RepairJobStatus::Cancelled->value)
            ->get();
        
        $this->hydrateServiceWorkers($jobs);

        $statusLabels = [
            RepairJobStatus::Confirmed->value => 'Confirmed',
            RepairJobStatus::Assigned->value => 'Assigned',
            RepairJobStatus::InProgress->value => 'In Progress',
            RepairJobStatus::Completed->value => 'Completed',
        ];

        $byStatus = [];
        foreach ($statusLabels as $status => $label) {
            $byStatus[] = [
                'label' => $label,
                'value' => $jobs->where('status', $status)->count(),
            ];
        }

        $serviceCounts = [];
        $tasksAssigned = 0;

        foreach ($jobs as $job) {
            foreach ($job->services as $service) {
                if ($service->workers->contains('id', $workerId)) {
                    $tasksAssigned++;
                    $name = $service->name;
                    $serviceCounts[$name] = ($serviceCounts[$name] ?? 0) + 1;
                }
            }
        }

        $byService = collect($serviceCounts)
            ->map(fn ($value, $label) => ['label' => $label, 'value' => $value])
            ->sortByDesc('value')
            ->values()
            ->take(6)
            ->all();

        $weekly = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();
            $count = $jobs->filter(
                fn ($job) => $job->updated_at >= $start && $job->updated_at <= $end
            )->count();

            $weekly[] = [
                'label' => $date->format('D'),
                'value' => $count,
            ];
        }

        return [
            'summary' => [
                'active' => $jobs->whereIn('status', [
                    RepairJobStatus::Confirmed->value,
                    RepairJobStatus::Assigned->value,
                    RepairJobStatus::InProgress->value,
                ])->count(),
                'assigned' => $jobs->where('status', RepairJobStatus::Assigned->value)->count(),
                'in_progress' => $jobs->where('status', RepairJobStatus::InProgress->value)->count(),
                'completed' => $jobs->where('status', RepairJobStatus::Completed->value)->count(),
                'tasks_assigned' => $tasksAssigned,
            ],
            'by_status' => $byStatus,
            'by_service' => $byService,
            'weekly' => $weekly,
        ];
    }
    
    public function getRepairJob($id)
    {
        $job = RepairJob::with([
            'vehicle.user',
            'services.requiredWorkerType',
            'invoice',
            'rating',
            'repairJobServices.items.inventory'
        ])->find($id);
        
        $this->hydrateServiceWorkers($job);
        
        return $job;
    }

    private function hydrateServiceWorkers($jobs)
    {
        if (!$jobs || (is_iterable($jobs) && $jobs->isEmpty())) {
            return;
        }

        $iterableJobs = is_iterable($jobs) ? $jobs : [$jobs];
        $pivotIds = [];

        foreach ($iterableJobs as $job) {
            foreach ($job->services as $service) {
                if ($service->pivot) {
                    $pivotIds[] = $service->pivot->id;
                }
            }
        }

        if (empty($pivotIds)) {
            return;
        }

        $workers = \App\Models\RepairJobServiceWorker::with('worker')
            ->whereIn('repair_job_service_id', $pivotIds)
            ->get()
            ->groupBy('repair_job_service_id');

        foreach ($iterableJobs as $job) {
            foreach ($job->services as $service) {
                if ($service->pivot && isset($workers[$service->pivot->id])) {
                    $serviceWorkers = $workers[$service->pivot->id]->pluck('worker')->filter();
                    $service->setRelation('workers', $serviceWorkers);
                } else {
                    $service->setRelation('workers', collect());
                }
            }
        }
    }

    public function startServiceWork(int $workerId, int $repairJobId, int $repairJobServiceId)
    {
        return DB::transaction(function () use ($workerId, $repairJobId, $repairJobServiceId) {
            $repairJobService = RepairJobServiceLine::where('id', $repairJobServiceId)
                ->where('repair_job_id', $repairJobId)
                ->firstOrFail();

            $isAssigned = RepairJobServiceWorker::where('repair_job_service_id', $repairJobServiceId)
                ->where('worker_id', $workerId)
                ->exists();

            if (!$isAssigned) {
                throw new AuthorizationException('You are not assigned to this service.');
            }

            if ($repairJobService->status === 'in_progress') {
                return $this->getRepairJob($repairJobId);
            }

            if (!in_array($repairJobService->status, ['assigned', 'pending'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'This service can no longer be started.',
                ]);
            }

            $hasOtherInProgress = RepairJobServiceLine::where('repair_job_id', $repairJobId)
                ->where('id', '!=', $repairJobServiceId)
                ->where('status', 'in_progress')
                ->exists();

            if ($hasOtherInProgress) {
                throw ValidationException::withMessages([
                    'status' => 'Another service is already in progress on this job.',
                ]);
            }

            $repairJobService->update(['status' => 'in_progress']);

            $repairJob = RepairJob::findOrFail($repairJobId);

            if ($repairJob->status !== RepairJobStatus::InProgress->value) {
                $repairJob->status = RepairJobStatus::InProgress;
                $repairJob->save();
                event(new RepairJobStatusUpdated($repairJob));
            }

            return $this->getRepairJob($repairJobId);
        });
    }

    public function completeServiceWork(int $workerId, int $repairJobId, int $repairJobServiceId)
    {
        return DB::transaction(function () use ($workerId, $repairJobId, $repairJobServiceId) {
            $repairJobService = RepairJobServiceLine::where('id', $repairJobServiceId)
                ->where('repair_job_id', $repairJobId)
                ->firstOrFail();

            $isAssigned = RepairJobServiceWorker::where('repair_job_service_id', $repairJobServiceId)
                ->where('worker_id', $workerId)
                ->exists();

            if (!$isAssigned) {
                throw new AuthorizationException('You are not assigned to this service.');
            }

            if ($repairJobService->status === 'completed') {
                return $this->getRepairJob($repairJobId);
            }

            if ($repairJobService->status !== 'in_progress') {
                throw ValidationException::withMessages([
                    'status' => 'Only an in-progress service can be marked complete.',
                ]);
            }

            $repairJobService->update(['status' => 'completed']);

            $repairJob = RepairJob::findOrFail($repairJobId);
            $allServices = RepairJobServiceLine::where('repair_job_id', $repairJobId)->get();
            $allCompleted = $allServices->every(
                fn ($service) => $service->status === 'completed'
            );

            if ($allCompleted) {
                $repairJob->status = RepairJobStatus::Completed;
                $repairJob->end_date = now();
                $repairJob->save();
                event(new RepairJobStatusUpdated($repairJob));
            } elseif ($repairJob->status !== RepairJobStatus::InProgress->value) {
                $repairJob->status = RepairJobStatus::InProgress;
                $repairJob->save();
                event(new RepairJobStatusUpdated($repairJob));
            }

            return $this->getRepairJob($repairJobId);
        });
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