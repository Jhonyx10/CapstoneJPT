<?php

namespace App\Services;

use App\Models\Invoices;

class InvoiceService
{
    public function getAll()
    {
        return Invoices::all();
    }

    public function getById($id)
    {
        return Invoices::find($id);
    }

    public function create($data)
    {
        return Invoices::create($data);
    }

    public function update($data, $id)
    {
        $invoice = Invoices::find($id);
        $invoice->update($data);
        return $invoice;
    }

    public function delete($id)
    {
        $invoice = Invoices::find($id);
        $invoice->delete();
        return $invoice;
    }
}