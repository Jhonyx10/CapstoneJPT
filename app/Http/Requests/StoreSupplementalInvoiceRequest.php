<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplementalInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'required|exists:invoices,id',
            'labor_cost' => 'required|numeric|min:0',
            'material_cost' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => 'A parent invoice is required.',
            'parent_id.exists' => 'The selected parent invoice is invalid.',
            'labor_cost.required' => 'Labor cost is required.',
            'labor_cost.numeric' => 'Labor cost must be a number.',
        ];
    }
}
