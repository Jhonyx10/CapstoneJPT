<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\ItemCategory;
use Illuminate\Support\Facades\DB;
use App\Enums\InventoryActionStatus;

class InventoryService
{
    public function createCategory($data)
    {
        return DB::transaction(function () use ($data) {
            $category = ItemCategory::create($data);
            return $category;
        });
    }

    public function updateCategory($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $category = ItemCategory::findOrFail($id);
            $category->update($data);
            return $category;
        });
    }

    public function deleteCategory($id)
    {
        return DB::transaction(function () use ($id) {
            $category = ItemCategory::findOrFail($id);
            $category->delete();
            return true;
        });
    }

    public function getAllCategories()
    {
        return ItemCategory::all();
    }

    public function getAllInventories()
    {
        return Inventory::with('category')->get();
    }

    public function createInventory($data)
    {
        return DB::transaction(function () use ($data) {
            $inventory = Inventory::create($data);
            
            // Create initial stock log
            InventoryLog::create([
                'inventory_id' => $inventory->id,
                'quantity' => $data['quantity_in_stock'],
                'type' => 'in',
                'notes' => 'Initial stock',
                'user_id' => auth()->id(),
                'action' => InventoryActionStatus::Restock,
            ]);

            return $inventory;
        });
    }

    public function updateInventory($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $inventory = Inventory::findOrFail($id);
            $inventory->update($data);

            return $inventory;
        });
    }

    public function deleteInventory($id)
    {
        return DB::transaction(function () use ($id) {
            $inventory = Inventory::findOrFail($id);
            $inventory->delete();

            return true;
        });
    }

    public function getInventoryById($id)
    {
        return Inventory::with('category')->findOrFail($id);
    }

    public function getInventoryLogs()
    {
        return InventoryLog::with('inventory','loggedBy')->get();
    }
}