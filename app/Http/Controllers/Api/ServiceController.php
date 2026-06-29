<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CompanyService;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{
    protected $companyService;

    public function __construct(CompanyService $companyService) {
        $this->companyService = $companyService;
    }

    public function index() {
        $services = $this->companyService->getAll();
        return response()->json($services);
    }

    public function store(ServiceRequest $request) {
        $service = $this->companyService->storeService($request->validated());
        return response()->json($service, 201);
    }

    public function update(ServiceRequest $request, $id) {
        $service = $this->companyService->updateService($request->all(), $id);
        return response()->json($service);
    }

    public function destroy($id) {
        $service = $this->companyService->deleteService($id);
        return response()->json($service);
    }
}
