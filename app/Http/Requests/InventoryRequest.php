<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
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
            'item_name' => 'required|string|max:255',
            'category_id' => 'required|exists:item_categories,id',
            'quantity_in_stock' => 'required|integer|min:0',
            'min_stock_alert' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'item_name.required' => 'The item name field is required.',
            'category_id.required' => 'The category field is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'quantity_in_stock.required' => 'The quantity field is required.',
            'quantity_in_stock.integer' => 'The quantity must be an integer.',
            'quantity_in_stock.min' => 'The quantity must be at least 0.',
            'min_stock_alert.required' => 'The minimum stock alert field is required.',
            'min_stock_alert.integer' => 'The minimum stock alert must be an integer.',
            'min_stock_alert.min' => 'The minimum stock alert must be at least 0.',
            'unit.required' => 'The unit field is required.',
            'unit_price.required' => 'The unit price field is required.',
            'unit_price.numeric' => 'The unit price must be a number.',
            'unit_price.min' => 'The unit price must be at least 0.',
            'notes.string' => 'The notes must be a string.',
            'notes.max' => 'The notes must be at most 255 characters.',
        ];
    }
}
