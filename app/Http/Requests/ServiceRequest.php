<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ServiceRequest extends FormRequest
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
            'name' => 'required|string',
            'worker_type' => 'required|string',
            'base_price' => 'required|numeric',
            'item_category_id' => 'nullable|exists:item_categories,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Service name is required',
            'worker_type.required' => 'Worker type is required',
            'base_price.required' => 'Base price is required',
            'item_category_id.exists' => 'Selected item category is invalid',
        ];
    }
}
