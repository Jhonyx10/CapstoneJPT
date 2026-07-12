<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class SearchRepairBooking extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   protected function prepareForValidation(): void
{
    Log::info('all route params', [
        'names' => $this->route()?->parameterNames(),
        'values' => $this->route()?->parameters(),
    ]);

    $this->merge([
        'reference_number' => $this->route('reference_number'),
    ]);
}

    public function rules(): array
    {
        return [
            'reference_number' => 'required|string|exists:repair_jobs,reference_number',
        ];
    }

    public function messages(): array
    {
        return [
            'reference_number.required' => 'Please enter a reference number.',
            'reference_number.exists' => 'Reference number not found.',
        ];
    }
}