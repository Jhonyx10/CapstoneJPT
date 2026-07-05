<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;
use App\Enums\InvoiceStatus;

class InvoiceService
{
    public function getAll()
    {
        return Invoice::with([
            'parent:id,invoice_number',
            'children:id,parent_id,invoice_number,status,total_amount,type',
        ])
            ->orderByDesc('created_at')
            ->get();
    }

    public function getById($id)
    {
        return Invoice::with([
            'payments',
            'parent.payments',
            'children.payments',
            'repairJob.vehicle',
            'booking.vehicle',
            'booking.customer',
        ])->findOrFail($id);
    }

    public function create($data)
    {
        if (!empty($data['parent_id'])) {
            return $this->createSupplemental($data);
        }

        return Invoice::create($data);
    }

    protected function createSupplemental(array $data): Invoice
    {
        $parent = Invoice::findOrFail($data['parent_id']);

        $labor = (float) ($data['labor_cost'] ?? 0);
        $material = (float) ($data['material_cost'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $total = round($labor + $material + $tax, 2);

        $addonIndex = $parent->children()->count() + 1;

        return Invoice::create([
            'parent_id' => $parent->id,
            'repair_job_id' => $parent->repair_job_id,
            'booking_id' => $parent->booking_id,
            'type' => 'supplemental_invoice',
            'version' => ($parent->version ?? 1) + $addonIndex,
            'invoice_number' => 'INV-ADD-' . strtoupper(Str::random(8)),
            'labor_cost' => $labor,
            'material_cost' => $material,
            'tax' => $tax,
            'total_amount' => $total,
            'amount_due' => $total,
            'status' => InvoiceStatus::UNPAID,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update($data, $id)
    {
        $invoice = Invoice::find($id);
        $invoice->update($data);
        return $invoice;
    }

    public function delete($id)
    {
        $invoice = Invoice::find($id);
        $invoice->delete();
        return $invoice;
    }
}