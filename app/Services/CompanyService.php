<?php

namespace App\Services;

use App\Models\Service;

class CompanyService
{
    public function getAll() {
        return Service::with('requiredWorkerType.users','itemCategory.inventories')->get();
    }

    public function storeService($data) {
        return Service::create($data);
    }

        public function updateService($data, $id) {
        $service = Service::find($id);
        $service->update($data);
        return $service;
    }

    public function deleteService($id) {
        $service = Service::find($id);
        $service->delete();
        return $service;
    }
}