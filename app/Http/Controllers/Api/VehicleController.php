<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\VehicleRequest;
use App\Services\VehicleService;

class VehicleController extends Controller
{
    protected $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    public function index() {
        $vehicles = $this->vehicleService->getAll();
        return response()->json($vehicles);
    }

    public function store(VehicleRequest $request) {

        $vehicle = $this->vehicleService->registerVehicleWithJob($request->validated());
        return response()->json($vehicle, 201);
    }

    public function update(Request $request, $id) {
        $vehicle = $this->vehicleService->update($request->all(), $id);
        return response()->json($vehicle);
    }

    public function destroy($id) {
        $vehicle = $this->vehicleService->delete($id);
        return response()->json($vehicle);
    }
}
