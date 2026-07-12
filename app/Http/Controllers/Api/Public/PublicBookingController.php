<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VehicleService;
use App\Services\CompanyService;
use App\Services\RepairJobService;
use App\Http\Requests\VehicleRequest;
use App\Http\Requests\SearchRepairBooking;

class PublicBookingController extends Controller
{
    protected $vehicleService;
    protected $companyService;
    protected $repairJobService;

    public function __construct(VehicleService $vehicleService, CompanyService $companyService, RepairJobService $repairJobService) {
        $this->vehicleService = $vehicleService;
        $this->companyService = $companyService;
        $this->repairJobService = $repairJobService;
    }

    public function store(VehicleRequest $request) {
        $vehicle = $this->vehicleService->registerVehicleWithJob($request->all());
        return response()->json($vehicle, 201);
    }

    public function getServices() {
        $services = $this->companyService->getAll();
        return response()->json($services);
    }

    public function trackRepairBooking(SearchRepairBooking $request)
    {
        $repairJob = $this->repairJobService->trackRepairBooking($request->reference_number);

        if (!$repairJob) {
            return response()->json(['message' => 'Reference number not found.'], 404);
        }

        return response()->json($repairJob);
    }
}
