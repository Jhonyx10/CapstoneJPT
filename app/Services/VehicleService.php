<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\RepairJob;
use App\Models\Service as ServiceModel;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public function registerVehicleWithJob(array $data)
    {
        return DB::transaction(function () use ($data) {
            $vehicle = Vehicle::create([
                'user_id'        => $data['user_id'],
                'brand'          => $data['brand'],
                'model'          => $data['model'],
                'body_type'      => $data['body_type'],
                'engine_type'    => $data['engine_type'],
                'transmission'   => $data['transmission'],
                'chassis_number' => $data['chassis_number'],
                'plate_number'   => $data['plate_number'],
                'status'         => $data['status'],
            ]);

            $repairJob = RepairJob::create([
                'vehicle_id' => $vehicle->id,
                'status'     => 'pending',
            ]);

            $totalServicesCost = 0.00;
            if (!empty($data['service_ids'])) {
                $services = ServiceModel::whereIn('id', $data['service_ids'])->get();

                $pivotData = [];
                foreach ($services as $service) {
                    $pivotData[$service->id] = [
                        'actual_price' => $service->base_price,
                        'status'       => 'pending',
                    ];
                    $totalServicesCost += $service->base_price;
                }
                $repairJob->services()->attach($pivotData);
            }

            $repairJob->invoice()->create([
                'invoice_number' => 'INV-' . strtoupper(uniqid()),
                'labor_cost'     => $totalServicesCost,
                'material_cost'  => 0.00,
                'tax'            => 0.00,
                'total_amount'   => $totalServicesCost,
                'amount_due'     => $totalServicesCost,
                'status'         => 'unpaid',
            ]);

            $repairJob->update(['total_estimated_cost' => $totalServicesCost]);

            return $vehicle->load('repairJobs.invoice');
        });
    }

    public function getAll()
    {
        return Vehicle::all();
    }

    public function update($data, $id)
    {
        $vehicle = Vehicle::find($id);
        $vehicle->update($data);
        return $vehicle;
    }

    public function delete($id)
    {
        $vehicle = Vehicle::find($id);
        $vehicle->delete();
        return $vehicle;
    }
}
