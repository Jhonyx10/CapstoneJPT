<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\ItemCategory;
use App\Models\Inventory;
use App\Models\InventoryLog;
use App\Models\Invoice;
use App\Models\RepairJob;
use App\Enums\InventoryActionStatus;
use App\Enums\InvoiceStatus;
use App\Services\InvoiceService;

class InventoryService
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

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
                'notes' => 'New Item Added',
                'user_id' => auth()->id(),
                'action' => InventoryActionStatus::AddNewItem,
            ]);

            return $inventory;
        });
    }

    public function itemRestock($data)
    {
        return DB::transaction(function () use ($data) {
            InventoryLog::create([
                'inventory_id' => $data['inventory_id'],
                'quantity' => $data['quantity'],
                'type' => 'in',
                'notes' => $data['notes'] ?? 'Item Restocked',
                'user_id' => auth()->id(),
                'action' => InventoryActionStatus::Restock,
            ]);
            Inventory::where('id', $data['inventory_id'])->increment('quantity_in_stock', $data['quantity']);
        });
    }

   public function workerItemsRepairLog(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $repairJob = RepairJob::findOrFail($data['repair_job_id']);

            // The original invoice for this job — not a supplemental one itself.
            $parentInvoice = Invoice::where('repair_job_id', $repairJob->id)
                ->whereNull('parent_id')
                ->latest()
                ->first();

            if (!$parentInvoice) {
                throw ValidationException::withMessages([
                    'repair_job_id' => 'This repair job has no invoice to update.',
                ]);
            }

            $materialCost = 0.00;
            $usedItemNames = [];

            foreach ($data['items'] as $item) {
                $inventory = Inventory::findOrFail($item['inventory_id']);

                if ($inventory->quantity_in_stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Not enough stock for \"{$inventory->item_name}\". Available: {$inventory->quantity_in_stock}.",
                    ]);
                }

                // NOTE: assumes Inventory has a per-unit cost column — adjust
                // 'unit_cost' below to match your actual column name if different.
                $lineCost = $inventory->unit_price * $item['quantity'];
                $materialCost += $lineCost;
                $usedItemNames[] = "{$inventory->item_name} x{$item['quantity']}";

                InventoryLog::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => $item['quantity'],
                    'type' => 'out',
                    'notes' => $data['notes'] ?? 'Worker Items Repair',
                    'user_id' => auth()->id(),
                    'action' => InventoryActionStatus::RepairUsage,
                    'repair_job_id' => $repairJob->id,
                ]);

                $inventory->decrement('quantity_in_stock', $item['quantity']);
            }

            $materialCost = round($materialCost, 2);

            $supplemental = $this->invoiceService->createSupplemental([
                'parent_id' => $parentInvoice->id,
                'labor_cost' => 0,
                'material_cost' => $materialCost,
                'tax' => 0,
                'notes' => 'Materials used: ' . implode(', ', $usedItemNames),
            ]);

            $parentInvoice->update([
                'material_cost' => $parentInvoice->material_cost + $materialCost,
                'total_amount' => $parentInvoice->total_amount + $materialCost,
                'amount_due' => $parentInvoice->amount_due + $materialCost,
                'is_updated' => true,
            ]);

            return $supplemental;
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
        return InventoryLog::with('inventory','loggedBy')->orderBy('created_at', 'desc')->get();
    }
}