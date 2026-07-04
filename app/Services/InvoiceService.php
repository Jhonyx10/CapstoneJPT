<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public function getAll()
    {
        return Invoice::all();
    }

    public function getById($id)
    {
        return Invoice::find($id);
    }

    public function create($data)
    {
        return Invoice::create($data);
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