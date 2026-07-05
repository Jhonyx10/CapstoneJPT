<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\InvoiceService;
use App\Http\Requests\StoreSupplementalInvoiceRequest;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index()
    {
        return $this->invoiceService->getAll();
    }

    public function show($id)
    {
        return $this->invoiceService->getById($id);
    }

    public function store(StoreSupplementalInvoiceRequest $request)
    {
        $invoice = $this->invoiceService->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Additional invoice created successfully',
            'data' => $invoice->load(['parent', 'children']),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        return $this->invoiceService->update($request->all(), $id);
    }

    public function destroy($id)
    {
        return $this->invoiceService->delete($id);
    }
}
