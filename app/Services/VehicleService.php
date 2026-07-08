<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\RepairJob;
use App\Models\Service as ServiceModel;
use App\Enums\VehicleStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Enums\RepairJobStatus;
use App\Enums\InvoiceType;

class VehicleService
{
   public function registerVehicleWithJob(array $data)
{
    return DB::transaction(function () use ($data) {
        $status = VehicleStatus::from($data['status']);

        $vehicle = Vehicle::create([
            'user_id'        => auth()->id(),
            'brand'          => $data['brand'],
            'model'          => $data['model'],
            'body_type'      => $data['body_type'],
            'engine_type'    => $data['engine_type'],
            'transmission'   => $data['transmission'],
            'chassis_number' => "CHASSIS-" . $data['chassis_number'],
            'plate_number'   => "PLATE-" . $data['plate_number'],
            'image'          => $this->storeImage($data['image'] ?? null),
            'status'         => $status,
        ]);

        if (!$status->requiresRepairFlow()) {
            return $vehicle;
        }

        $repairJob = RepairJob::create([
            'vehicle_id' => $vehicle->id,
            'status'     => RepairJobStatus::Pending,
        ]);

        \Log::info('service_ids received:', ['data' => $data['service_ids'] ?? 'NOT SET']);
        \Log::info('service_items received:', ['data' => $data['service_items'] ?? 'NOT SET']);

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

            // Fetch the attached pivot records to get their IDs
            $attachedServices = $repairJob->services()->withPivot('id')->whereIn('service_id', $data['service_ids'])->get();
            \Log::info('attached services count:', ['count' => $attachedServices->count()]);
            
            $serviceItemsData = $data['service_items'] ?? [];
            
            $inventoryIds = [];
            foreach ($serviceItemsData as $serviceId => $itemIds) {
                $inventoryIds = array_merge($inventoryIds, $itemIds);
            }
            $inventories = \App\Models\Inventory::whereIn('id', $inventoryIds)->get()->keyBy('id');
            \Log::info('inventories fetched:', ['count' => $inventories->count(), 'keys' => $inventories->keys()]);

            $itemsToInsert = [];
            foreach ($attachedServices as $service) {
                $serviceId = $service->id;
                $pivotId = $service->pivot->id;
                $selectedItems = $serviceItemsData[$serviceId] ?? [];
                \Log::info("processing service {$serviceId}", ['pivotId' => $pivotId, 'selectedItems' => $selectedItems]);
                
                foreach ($selectedItems as $itemId) {
                    if (isset($inventories[$itemId])) {
                        $inv = $inventories[$itemId];
                        $itemsToInsert[] = [
                            'repair_job_service_id' => $pivotId,
                            'inventory_id' => $itemId,
                            'unit_price' => $inv->unit_price,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
            
            \Log::info('itemsToInsert:', ['data' => $itemsToInsert]);
            if (!empty($itemsToInsert)) {
                \App\Models\RepairJobServiceItem::insert($itemsToInsert);
            }
        }

        $totalAmount = $totalServicesCost;

        $repairJob->invoice()->create([
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'type' => InvoiceType::Estimated,
            'labor_cost'     => $totalServicesCost,
            'material_cost'  => 0.00,
            'tax'            => 0.00,
            'total_amount'   => $totalAmount,
            'amount_due'     => $totalAmount,
            'status'         => 'unpaid',
            'notes'          => 'Initial estimate. Final amount is subject to change based on actual materials used, fluid capacities, or additional component requirements discovered during teardown',
        ]);

        $repairJob->update(['total_estimated_cost' => $totalAmount]);

        return $vehicle->load('repairJobs.invoice');
    });
}

    public function getAll()
    {
        return Vehicle::latest()->paginate(6);
    }

    public function getVehicleId($id)
    {
        return Vehicle::find($id);
    }

    public function update($data, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            $this->deleteImage($vehicle->image);
            $data['image'] = $this->storeImage($data['image']);
        } else {
            unset($data['image']);
        }

        $vehicle->update($data);

        return $vehicle;
    }

    public function delete($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->deleteImage($vehicle->image);
        $vehicle->delete();

        return $vehicle;
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if (!$image) {
            return null;
        }

        return $image->store('vehicles', 'public');
    }

    private function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
