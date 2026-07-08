<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|exists:inventories,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'inventory_id.required' => 'The inventory ID field is required.',
            'inventory_id.exists' => 'The selected inventory is invalid.',
            'quantity.required' => 'The quantity field is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 1.',
            'notes.string' => 'The notes must be a string.',
            'notes.max' => 'The notes must be at most 255 characters.',
        ];
    }
}
