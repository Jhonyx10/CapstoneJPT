<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkerItemRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'repair_job_id' => 'required|exists:repair_jobs,id',
            'notes' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:inventories,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'repair_job_id.required' => 'Repair job is required.',
            'repair_job_id.exists' => 'The selected repair job does not exist.',
            'items.required' => 'At least one item is required.',
            'items.array' => 'Items must be a list.',
            'items.min' => 'At least one item is required.',
            'items.*.inventory_id.required' => 'Each item must reference an inventory item.',
            'items.*.inventory_id.exists' => 'One of the selected inventory items does not exist.',
            'items.*.quantity.required' => 'Each item must have a quantity.',
            'items.*.quantity.integer' => 'Quantity must be a whole number.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'notes.string' => 'Notes must be a string.',
            'notes.max' => 'Notes cannot exceed 255 characters.',
        ];
    }
}