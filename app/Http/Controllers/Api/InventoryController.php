<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InventoryService;
use App\Http\Requests\InventoryRequest;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index()
    {
        $inventories = $this->inventoryService->getAllInventories();
        return response()->json([
            'status' => 'success',
            'message' => 'Inventories retrieved successfully',
            'data' => $inventories,
        ]);
    }

    public function store(InventoryRequest $request)
    {
        $inventory = $this->inventoryService->createInventory($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory created successfully',
            'data' => $inventory,
        ]);
    }

    public function show($id)
    {
        $inventory = $this->inventoryService->getInventoryById($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory retrieved successfully',
            'data' => $inventory,
        ]);
    }

    public function update(InventoryRequest $request, $id)
    {
        $inventory = $this->inventoryService->updateInventory($id, $request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory updated successfully',
            'data' => $inventory,
        ]);
    }

    public function destroy($id)
    {
        $this->inventoryService->deleteInventory($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory deleted successfully',
        ]);
    }

    public function getInventoryLogs()
    {
        $logs = $this->inventoryService->getInventoryLogs();
        return response()->json([
            'status' => 'success',
            'message' => 'Inventory logs retrieved successfully',
            'data' => $logs,
        ]);
    }
}
