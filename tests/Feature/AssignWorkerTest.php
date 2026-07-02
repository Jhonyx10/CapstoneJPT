<?php

namespace Tests\Feature;

use App\Models\RepairJob;
use App\Models\RepairJobService;
use App\Models\RepairJobServiceWorker;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignWorkerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_multiple_workers_successfully()
    {
        // 1. Create Roles
        $adminRole = Role::create(['name' => 'Admin']);
        $mechanicRole = Role::create(['name' => 'Mechanic']);

        // 2. Create Users
        $user = User::factory()->create(['role_id' => $adminRole->id]);
        $worker1 = User::factory()->create(['role_id' => $mechanicRole->id]);
        $worker2 = User::factory()->create(['role_id' => $mechanicRole->id]);

        // 3. Create Vehicle
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'body_type' => 'Sedan',
            'engine_type' => 'Petrol',
            'transmission' => 'Automatic',
            'chassis_number' => 'ABC123456789',
            'plate_number' => 'XYZ-987',
            'status' => 'for_repair',
        ]);

        // 4. Create Service
        $service = Service::create([
            'name' => 'Oil Change',
            'base_price' => 50.00,
            'worker_type' => $mechanicRole->id,
        ]);

        // 5. Create Repair Job
        $repairJob = RepairJob::create([
            'vehicle_id' => $vehicle->id,
            'status' => 'pending',
            'total_estimated_cost' => 50.00,
        ]);

        // 6. Create Repair Job Service
        $repairJobService1 = RepairJobService::create([
            'repair_job_id' => $repairJob->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'actual_price' => 50.00,
        ]);

        $repairJobService2 = RepairJobService::create([
            'repair_job_id' => $repairJob->id,
            'service_id' => $service->id,
            'status' => 'pending',
            'actual_price' => 60.00,
        ]);

        $worker3 = User::factory()->create(['role_id' => $mechanicRole->id]);
        $worker4 = User::factory()->create(['role_id' => $mechanicRole->id]);

        // 7. Make API call posting bulk payload with nested and object formats
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/assign-workers', [
                [
                    'repair_job_service_id' => $repairJobService1->id,
                    'worker_ids' => [
                        ['worker_id' => $worker1->id],
                        ['worker_id' => $worker2->id],
                    ],
                ],
                [
                    'repair_job_service_id' => $repairJobService2->id,
                    'worker_ids' => [
                        $worker3->id,
                        $worker4->id,
                    ],
                ]
            ]);

        $response->assertStatus(201);

        // Verify service 1 assignments in the database
        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService1->id,
            'worker_id' => $worker1->id,
        ]);

        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService1->id,
            'worker_id' => $worker2->id,
        ]);

        // Verify service 2 assignments in the database
        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService2->id,
            'worker_id' => $worker3->id,
        ]);

        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService2->id,
            'worker_id' => $worker4->id,
        ]);

        // 8. Verify a single direct assignment object is also supported (wrapped dynamically)
        $responseSingle = $this->actingAs($user, 'sanctum')
            ->postJson('/api/assign-workers', [
                'repair_job_service_id' => $repairJobService1->id,
                'worker_ids' => [
                    ['worker_id' => $worker3->id]
                ],
            ]);

        $responseSingle->assertStatus(201);

        // Verify service 1 contains the new worker3, and worker1/worker2 are STILL preserved (non-destructive)
        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService1->id,
            'worker_id' => $worker3->id,
        ]);

        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService1->id,
            'worker_id' => $worker1->id,
        ]);

        $this->assertDatabaseHas('repair_job_service_workers', [
            'repair_job_service_id' => $repairJobService1->id,
            'worker_id' => $worker2->id,
        ]);
    }
}
